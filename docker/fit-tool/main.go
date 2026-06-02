// Command fit-tool decodes a Garmin .FIT file and writes its contents to
// stdout as JSON. It is a thin wrapper around github.com/muktihari/fit so the
// PHP application can decode FIT files through a Symfony Process call.
//
// Usage:
//
//	fit-tool <path-to-fit-file>
//
// Output shape:
//
//	{
//	  "files": [
//	    {
//	      "profileVersion": 2132,
//	      "messages": [
//	        {"name": "record", "num": 20, "fields": [
//	          {"name": "position_lat", "num": 0, "units": "semicircles", "value": 533590487},
//	          {"name": "speed", "num": 6, "units": "m/s", "value": 4.123}
//	        ]}
//	      ]
//	    }
//	  ]
//	}
//
// Field values already have their FIT scale/offset applied. Invalid/unset
// numeric fields (encoded as NaN by the FIT profile) are emitted as null so the
// document is always valid JSON. A single .FIT file may contain several chained
// FIT files, hence "files" is an array.
//
// On success it prints the JSON document and exits 0. On any error it prints a
// message to stderr and exits non-zero.
package main

import (
	"bufio"
	"encoding/json"
	"fmt"
	"math"
	"os"

	"github.com/muktihari/fit/decoder"
	"github.com/muktihari/fit/kit/scaleoffset"
)

type outFile struct {
	ProfileVersion uint16       `json:"profileVersion"`
	Messages       []outMessage `json:"messages"`
}

type outMessage struct {
	Name   string     `json:"name"`
	Num    uint16     `json:"num"`
	Fields []outField `json:"fields"`
}

type outField struct {
	Name  string `json:"name"`
	Num   byte   `json:"num"`
	Units string `json:"units,omitempty"`
	Value any    `json:"value"`
}

func main() {
	if len(os.Args) != 2 {
		fmt.Fprintln(os.Stderr, "usage: fit-tool <path-to-fit-file>")
		os.Exit(2)
	}

	if err := run(os.Args[1]); err != nil {
		fmt.Fprintf(os.Stderr, "fit-tool: %v\n", err)
		os.Exit(1)
	}
}

func run(path string) error {
	f, err := os.Open(path)
	if err != nil {
		return fmt.Errorf("open file: %w", err)
	}
	defer f.Close()

	dec := decoder.New(bufio.NewReader(f))

	files := make([]outFile, 0, 1)
	// A .FIT file may contain multiple chained FIT files; decode them all.
	for dec.Next() {
		fit, err := dec.Decode()
		if err != nil {
			return fmt.Errorf("decode: %w", err)
		}

		file := outFile{
			ProfileVersion: fit.FileHeader.ProfileVersion,
			Messages:       make([]outMessage, 0, len(fit.Messages)),
		}

		for _, mesg := range fit.Messages {
			fields := make([]outField, 0, len(mesg.Fields))
			for _, field := range mesg.Fields {
				if field.FieldBase == nil {
					continue
				}

				scale := field.Scale
				if scale == 0 {
					scale = 1
				}

				value := scaleoffset.ApplyAny(field.Value.Any(), scale, field.Offset)

				fields = append(fields, outField{
					Name:  field.Name,
					Num:   field.Num,
					Units: field.Units,
					Value: sanitize(value),
				})
			}

			file.Messages = append(file.Messages, outMessage{
				Name:   mesg.Num.String(),
				Num:    uint16(mesg.Num),
				Fields: fields,
			})
		}

		files = append(files, file)
	}

	enc := json.NewEncoder(os.Stdout)
	if err := enc.Encode(map[string]any{"files": files}); err != nil {
		return fmt.Errorf("encode json: %w", err)
	}

	return nil
}

// sanitize replaces non-finite floats (the FIT "invalid" sentinel) with nil so
// the result is always valid JSON, recursing into float slices.
func sanitize(v any) any {
	switch x := v.(type) {
	case float64:
		return finiteOrNil(x)
	case float32:
		return finiteOrNil(float64(x))
	case []float64:
		out := make([]any, len(x))
		for i, e := range x {
			out[i] = finiteOrNil(e)
		}
		return out
	case []float32:
		out := make([]any, len(x))
		for i, e := range x {
			out[i] = finiteOrNil(float64(e))
		}
		return out
	default:
		return v
	}
}

func finiteOrNil(f float64) any {
	if math.IsNaN(f) || math.IsInf(f, 0) {
		return nil
	}
	return f
}
