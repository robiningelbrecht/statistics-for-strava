const earthRadius = 63710088e-1;
const factors = {
    centimeters: earthRadius * 100,
    centimetres: earthRadius * 100,
    degrees: 360 / (2 * Math.PI),
    feet: earthRadius * 3.28084,
    inches: earthRadius * 39.37,
    kilometers: earthRadius / 1e3,
    kilometres: earthRadius / 1e3,
    meters: earthRadius,
    metres: earthRadius,
    miles: earthRadius / 1609.344,
    millimeters: earthRadius * 1e3,
    millimetres: earthRadius * 1e3,
    nauticalmiles: earthRadius / 1852,
    radians: 1,
    yards: earthRadius * 1.0936
};

const __defProp = Object.defineProperty;
const __defProps = Object.defineProperties;
const __getOwnPropDescs = Object.getOwnPropertyDescriptors;
const __getOwnPropSymbols = Object.getOwnPropertySymbols;
const __hasOwnProp = Object.prototype.hasOwnProperty;
const __propIsEnum = Object.prototype.propertyIsEnumerable;
const __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
const __spreadValues = (a, b) => {
    for (var prop in b || (b = {}))
        if (__hasOwnProp.call(b, prop))
            __defNormalProp(a, prop, b[prop]);
    if (__getOwnPropSymbols)
        for (var prop of __getOwnPropSymbols(b)) {
            if (__propIsEnum.call(b, prop))
                __defNormalProp(a, prop, b[prop]);
        }
    return a;
};
const __spreadProps = (a, b) => __defProps(a, __getOwnPropDescs(b));

function bearing(start, end, options = {}) {
    if (options.final === true) {
        return calculateFinalBearing(start, end);
    }
    const coordinates1 = getCoord(start);
    const coordinates2 = getCoord(end);
    const lon1 = degreesToRadians(coordinates1[0]);
    const lon2 = degreesToRadians(coordinates2[0]);
    const lat1 = degreesToRadians(coordinates1[1]);
    const lat2 = degreesToRadians(coordinates2[1]);
    const a = Math.sin(lon2 - lon1) * Math.cos(lat2);
    const b = Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(lon2 - lon1);
    return radiansToDegrees(Math.atan2(a, b));
}
function calculateFinalBearing(start, end) {
    let bear = bearing(end, start);
    bear = (bear + 180) % 360;
    return bear;
}

function distance(from, to, options = {}) {
    var coordinates1 = getCoord(from);
    var coordinates2 = getCoord(to);
    var dLat = degreesToRadians(coordinates2[1] - coordinates1[1]);
    var dLon = degreesToRadians(coordinates2[0] - coordinates1[0]);
    var lat1 = degreesToRadians(coordinates1[1]);
    var lat2 = degreesToRadians(coordinates2[1]);
    var a = Math.pow(Math.sin(dLat / 2), 2) + Math.pow(Math.sin(dLon / 2), 2) * Math.cos(lat1) * Math.cos(lat2);
    return radiansToLength(
        2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)),
        options.units
    );
}

function feature(geom, properties, options = {}) {
    const feat = { type: "Feature" };
    if (options.id === 0 || options.id) {
        feat.id = options.id;
    }
    if (options.bbox) {
        feat.bbox = options.bbox;
    }
    feat.properties = properties || {};
    feat.geometry = geom;
    return feat;
}
function point(coordinates, properties, options = {}) {
    if (!coordinates) {
        throw new Error("coordinates is required");
    }
    if (!Array.isArray(coordinates)) {
        throw new Error("coordinates must be an Array");
    }
    if (coordinates.length < 2) {
        throw new Error("coordinates must be at least 2 numbers long");
    }
    if (!isNumber(coordinates[0]) || !isNumber(coordinates[1])) {
        throw new Error("coordinates must contain numbers");
    }
    const geom = {
        type: "Point",
        coordinates
    };
    return feature(geom, properties, options);
}
function lineString(coordinates, properties, options = {}) {
    if (coordinates.length < 2) {
        throw new Error("coordinates must be an array of two or more positions");
    }
    const geom = {
        type: "LineString",
        coordinates
    };
    return feature(geom, properties, options);
}
function radiansToLength(radians, units = "kilometers") {
    const factor = factors[units];
    if (!factor) {
        throw new Error(units + " units is invalid");
    }
    return radians * factor;
}
function lengthToRadians(distance, units = "kilometers") {
    const factor = factors[units];
    if (!factor) {
        throw new Error(units + " units is invalid");
    }
    return distance / factor;
}
function radiansToDegrees(radians) {
    const normalisedRadians = radians % (2 * Math.PI);
    return normalisedRadians * 180 / Math.PI;
}
function degreesToRadians(degrees) {
    const normalisedDegrees = degrees % 360;
    return normalisedDegrees * Math.PI / 180;
}
function convertLength(length, originalUnit = "kilometers", finalUnit = "kilometers") {
    if (!(length >= 0)) {
        throw new Error("length must be a positive number");
    }
    return radiansToLength(lengthToRadians(length, originalUnit), finalUnit);
}
function isNumber(num) {
    return !isNaN(num) && num !== null && !Array.isArray(num);
}

function getCoord(coord) {
    if (!coord) {
        throw new Error("coord is required");
    }
    if (!Array.isArray(coord)) {
        if (coord.type === "Feature" && coord.geometry !== null && coord.geometry.type === "Point") {
            return [...coord.geometry.coordinates];
        }
        if (coord.type === "Point") {
            return [...coord.coordinates];
        }
    }
    if (Array.isArray(coord) && coord.length >= 2 && !Array.isArray(coord[0]) && !Array.isArray(coord[1])) {
        return [...coord];
    }
    throw new Error("coord must be GeoJSON Point or an Array of numbers");
}
function getCoords(coords) {
    if (Array.isArray(coords)) {
        return coords;
    }
    if (coords.type === "Feature") {
        if (coords.geometry !== null) {
            return coords.geometry.coordinates;
        }
    } else {
        if (coords.coordinates) {
            return coords.coordinates;
        }
    }
    throw new Error(
        "coords must be GeoJSON Feature, Geometry Object or an Array"
    );
}
function featureOf(feature, type, name) {
    if (!feature) {
        throw new Error("No feature passed");
    }
    if (!name) {
        throw new Error(".featureOf() requires a name");
    }
    if (!feature || feature.type !== "Feature" || !feature.geometry) {
        throw new Error(
            "Invalid input to " + name + ", Feature with geometry required"
        );
    }
    if (!feature.geometry || feature.geometry.type !== type) {
        throw new Error(
            "Invalid input to " + name + ": must be a " + type + ", given " + feature.geometry.type
        );
    }
}

function coordEach(geojson, callback, excludeWrapCoord) {
    if (geojson === null) return;
    var j, k, l, geometry, stopG, coords, geometryMaybeCollection, wrapShrink = 0, coordIndex = 0, isGeometryCollection, type = geojson.type, isFeatureCollection = type === "FeatureCollection", isFeature = type === "Feature", stop = isFeatureCollection ? geojson.features.length : 1;
    for (var featureIndex = 0; featureIndex < stop; featureIndex++) {
        geometryMaybeCollection = isFeatureCollection ? geojson.features[featureIndex].geometry : isFeature ? geojson.geometry : geojson;
        isGeometryCollection = geometryMaybeCollection ? geometryMaybeCollection.type === "GeometryCollection" : false;
        stopG = isGeometryCollection ? geometryMaybeCollection.geometries.length : 1;
        for (var geomIndex = 0; geomIndex < stopG; geomIndex++) {
            var multiFeatureIndex = 0;
            var geometryIndex = 0;
            geometry = isGeometryCollection ? geometryMaybeCollection.geometries[geomIndex] : geometryMaybeCollection;
            if (geometry === null) continue;
            coords = geometry.coordinates;
            var geomType = geometry.type;
            wrapShrink = excludeWrapCoord && (geomType === "Polygon" || geomType === "MultiPolygon") ? 1 : 0;
            switch (geomType) {
                case null:
                    break;
                case "Point":
                    if (callback(
                        coords,
                        coordIndex,
                        featureIndex,
                        multiFeatureIndex,
                        geometryIndex
                    ) === false)
                        return false;
                    coordIndex++;
                    multiFeatureIndex++;
                    break;
                case "LineString":
                case "MultiPoint":
                    for (j = 0; j < coords.length; j++) {
                        if (callback(
                            coords[j],
                            coordIndex,
                            featureIndex,
                            multiFeatureIndex,
                            geometryIndex
                        ) === false)
                            return false;
                        coordIndex++;
                        if (geomType === "MultiPoint") multiFeatureIndex++;
                    }
                    if (geomType === "LineString") multiFeatureIndex++;
                    break;
                case "Polygon":
                case "MultiLineString":
                    for (j = 0; j < coords.length; j++) {
                        for (k = 0; k < coords[j].length - wrapShrink; k++) {
                            if (callback(
                                coords[j][k],
                                coordIndex,
                                featureIndex,
                                multiFeatureIndex,
                                geometryIndex
                            ) === false)
                                return false;
                            coordIndex++;
                        }
                        if (geomType === "MultiLineString") multiFeatureIndex++;
                        if (geomType === "Polygon") geometryIndex++;
                    }
                    if (geomType === "Polygon") multiFeatureIndex++;
                    break;
                case "MultiPolygon":
                    for (j = 0; j < coords.length; j++) {
                        geometryIndex = 0;
                        for (k = 0; k < coords[j].length; k++) {
                            for (l = 0; l < coords[j][k].length - wrapShrink; l++) {
                                if (callback(
                                    coords[j][k][l],
                                    coordIndex,
                                    featureIndex,
                                    multiFeatureIndex,
                                    geometryIndex
                                ) === false)
                                    return false;
                                coordIndex++;
                            }
                            geometryIndex++;
                        }
                        multiFeatureIndex++;
                    }
                    break;
                case "GeometryCollection":
                    for (j = 0; j < geometry.geometries.length; j++)
                        if (coordEach(geometry.geometries[j], callback, excludeWrapCoord) === false)
                            return false;
                    break;
                default:
                    throw new Error("Unknown Geometry Type");
            }
        }
    }
}
function geomEach(geojson, callback) {
    var i, j, g, geometry, stopG, geometryMaybeCollection, isGeometryCollection, featureProperties, featureBBox, featureId, featureIndex = 0, isFeatureCollection = geojson.type === "FeatureCollection", isFeature = geojson.type === "Feature", stop = isFeatureCollection ? geojson.features.length : 1;
    for (i = 0; i < stop; i++) {
        geometryMaybeCollection = isFeatureCollection ? geojson.features[i].geometry : isFeature ? geojson.geometry : geojson;
        featureProperties = isFeatureCollection ? geojson.features[i].properties : isFeature ? geojson.properties : {};
        featureBBox = isFeatureCollection ? geojson.features[i].bbox : isFeature ? geojson.bbox : void 0;
        featureId = isFeatureCollection ? geojson.features[i].id : isFeature ? geojson.id : void 0;
        isGeometryCollection = geometryMaybeCollection ? geometryMaybeCollection.type === "GeometryCollection" : false;
        stopG = isGeometryCollection ? geometryMaybeCollection.geometries.length : 1;
        for (g = 0; g < stopG; g++) {
            geometry = isGeometryCollection ? geometryMaybeCollection.geometries[g] : geometryMaybeCollection;
            if (geometry === null) {
                if (callback(
                    null,
                    featureIndex,
                    featureProperties,
                    featureBBox,
                    featureId
                ) === false)
                    return false;
                continue;
            }
            switch (geometry.type) {
                case "Point":
                case "LineString":
                case "MultiPoint":
                case "Polygon":
                case "MultiLineString":
                case "MultiPolygon": {
                    if (callback(
                        geometry,
                        featureIndex,
                        featureProperties,
                        featureBBox,
                        featureId
                    ) === false)
                        return false;
                    break;
                }
                case "GeometryCollection": {
                    for (j = 0; j < geometry.geometries.length; j++) {
                        if (callback(
                            geometry.geometries[j],
                            featureIndex,
                            featureProperties,
                            featureBBox,
                            featureId
                        ) === false)
                            return false;
                    }
                    break;
                }
                default:
                    throw new Error("Unknown Geometry Type");
            }
        }
        featureIndex++;
    }
}
function flattenEach(geojson, callback) {
    geomEach(geojson, function(geometry, featureIndex, properties, bbox, id) {
        var type = geometry === null ? null : geometry.type;
        switch (type) {
            case null:
            case "Point":
            case "LineString":
            case "Polygon":
                if (callback(
                    feature(geometry, properties, { bbox, id }),
                    featureIndex,
                    0
                ) === false)
                    return false;
                return;
        }
        var geomType;
        switch (type) {
            case "MultiPoint":
                geomType = "Point";
                break;
            case "MultiLineString":
                geomType = "LineString";
                break;
            case "MultiPolygon":
                geomType = "Polygon";
                break;
        }
        for (var multiFeatureIndex = 0; multiFeatureIndex < geometry.coordinates.length; multiFeatureIndex++) {
            var coordinate = geometry.coordinates[multiFeatureIndex];
            var geom = {
                type: geomType,
                coordinates: coordinate
            };
            if (callback(feature(geom, properties), featureIndex, multiFeatureIndex) === false)
                return false;
        }
    });
}
function segmentEach(geojson, callback) {
    flattenEach(geojson, function(feature2, featureIndex, multiFeatureIndex) {
        var segmentIndex = 0;
        if (!feature2.geometry) return;
        var type = feature2.geometry.type;
        if (type === "Point" || type === "MultiPoint") return;
        var previousCoords;
        var previousFeatureIndex = 0;
        var previousMultiIndex = 0;
        var prevGeomIndex = 0;
        if (coordEach(
            feature2,
            function(currentCoord, coordIndex, featureIndexCoord, multiPartIndexCoord, geometryIndex) {
                if (previousCoords === void 0 || featureIndex > previousFeatureIndex || multiPartIndexCoord > previousMultiIndex || geometryIndex > prevGeomIndex) {
                    previousCoords = currentCoord;
                    previousFeatureIndex = featureIndex;
                    previousMultiIndex = multiPartIndexCoord;
                    prevGeomIndex = geometryIndex;
                    segmentIndex = 0;
                    return;
                }
                var currentSegment = lineString(
                    [previousCoords, currentCoord],
                    feature2.properties
                );
                if (callback(
                    currentSegment,
                    featureIndex,
                    multiFeatureIndex,
                    geometryIndex,
                    segmentIndex
                ) === false)
                    return false;
                segmentIndex++;
                previousCoords = currentCoord;
            }
        ) === false)
            return false;
    });
}

function nearestPointOnLine(lines, pt, options = {}) {
    if (!lines || !pt) {
        throw new Error("lines and pt are required arguments");
    }
    const ptPos = getCoord(pt);
    let closestPt = point([Infinity, Infinity], {
        dist: Infinity,
        index: -1,
        multiFeatureIndex: -1,
        location: -1
    });
    let length = 0;
    flattenEach(
        lines,
        function(line, _featureIndex, multiFeatureIndex) {
            const coords = getCoords(line);
            for (let i = 0; i < coords.length - 1; i++) {
                const start = point(coords[i]);
                const startPos = getCoord(start);
                const stop = point(coords[i + 1]);
                const stopPos = getCoord(stop);
                const sectionLength = distance(start, stop, options);
                let intersectPos;
                let wasEnd;
                if (stopPos[0] === ptPos[0] && stopPos[1] === ptPos[1]) {
                    [intersectPos, wasEnd] = [stopPos, true];
                } else if (startPos[0] === ptPos[0] && startPos[1] === ptPos[1]) {
                    [intersectPos, wasEnd] = [startPos, false];
                } else {
                    [intersectPos, wasEnd] = nearestPointOnSegment(
                        startPos,
                        stopPos,
                        ptPos
                    );
                }
                const intersectPt = point(intersectPos, {
                    dist: distance(pt, intersectPos, options),
                    multiFeatureIndex,
                    location: length + distance(start, intersectPos, options)
                });
                if (intersectPt.properties.dist < closestPt.properties.dist) {
                    closestPt = __spreadProps(__spreadValues({}, intersectPt), {
                        properties: __spreadProps(__spreadValues({}, intersectPt.properties), {
                            // Legacy behaviour where index progresses to next segment # if we
                            // went with the end point this iteration.
                            index: wasEnd ? i + 1 : i
                        })
                    });
                }
                length += sectionLength;
            }
        }
    );
    return closestPt;
}

function cross(v1, v2) {
    const [v1x, v1y, v1z] = v1;
    const [v2x, v2y, v2z] = v2;
    return [v1y * v2z - v1z * v2y, v1z * v2x - v1x * v2z, v1x * v2y - v1y * v2x];
}
function magnitude(v) {
    return Math.sqrt(Math.pow(v[0], 2) + Math.pow(v[1], 2) + Math.pow(v[2], 2));
}
function normalize(v) {
    const mag = magnitude(v);
    return [v[0] / mag, v[1] / mag, v[2] / mag];
}
function lngLatToVector(a) {
    const lat = degreesToRadians(a[1]);
    const lng = degreesToRadians(a[0]);
    return [
        Math.cos(lat) * Math.cos(lng),
        Math.cos(lat) * Math.sin(lng),
        Math.sin(lat)
    ];
}
function vectorToLngLat(v) {
    const [x, y, z] = v;
    const zClamp = Math.min(Math.max(z, -1), 1);
    const lat = radiansToDegrees(Math.asin(zClamp));
    const lng = radiansToDegrees(Math.atan2(y, x));
    return [lng, lat];
}
function nearestPointOnSegment(posA, posB, posC) {
    const A = lngLatToVector(posA);
    const B = lngLatToVector(posB);
    const C = lngLatToVector(posC);
    const segmentAxis = cross(A, B);
    if (segmentAxis[0] === 0 && segmentAxis[1] === 0 && segmentAxis[2] === 0) {
        if (dot(A, B) > 0) {
            return [[...posB], true];
        } else {
            return [[...posC], false];
        }
    }
    const targetAxis = cross(segmentAxis, C);
    if (targetAxis[0] === 0 && targetAxis[1] === 0 && targetAxis[2] === 0) {
        return [[...posB], true];
    }
    const intersectionAxis = cross(targetAxis, segmentAxis);
    const I1 = normalize(intersectionAxis);
    const I2 = [-I1[0], -I1[1], -I1[2]];
    const I = dot(C, I1) > dot(C, I2) ? I1 : I2;
    const segmentAxisNorm = normalize(segmentAxis);
    const cmpAI = dot(cross(A, I), segmentAxisNorm);
    const cmpIB = dot(cross(I, B), segmentAxisNorm);
    if (cmpAI >= 0 && cmpIB >= 0) {
        return [vectorToLngLat(I), false];
    }
    if (dot(A, C) > dot(B, C)) {
        return [[...posA], false];
    } else {
        return [[...posB], true];
    }
}

function pointToLineDistance(pt, line, options = {}) {
    var _a, _b;
    const method = (_a = options.method) != null ? _a : "geodesic";
    const units = (_b = options.units) != null ? _b : "kilometers";
    if (!pt) {
        throw new Error("pt is required");
    }
    if (Array.isArray(pt)) {
        pt = point(pt);
    } else if (pt.type === "Point") {
        pt = feature(pt);
    } else {
        featureOf(pt, "Point", "point");
    }
    if (!line) {
        throw new Error("line is required");
    }
    if (Array.isArray(line)) {
        line = lineString(line);
    } else if (line.type === "LineString") {
        line = feature(line);
    } else {
        featureOf(line, "LineString", "line");
    }
    let distance = Infinity;
    const p = pt.geometry.coordinates;
    segmentEach(line, (segment) => {
        if (segment) {
            const a = segment.geometry.coordinates[0];
            const b = segment.geometry.coordinates[1];
            const d = distanceToSegment(p, a, b, { method });
            if (d < distance) {
                distance = d;
            }
        }
    });
    return convertLength(distance, "degrees", units);
}
function distanceToSegment(p, a, b, options) {
    if (options.method === "geodesic") {
        const nearest = nearestPointOnLine(lineString([a, b]).geometry, p, {
            units: "degrees"
        });
        return nearest.properties.dist;
    }
    const v = [b[0] - a[0], b[1] - a[1]];
    const w = [p[0] - a[0], p[1] - a[1]];
    const c1 = dot(w, v);
    if (c1 <= 0) {
        return rhumbDistance(p, a, { units: "degrees" });
    }
    const c2 = dot(v, v);
    if (c2 <= c1) {
        return rhumbDistance(p, b, { units: "degrees" });
    }
    const b2 = c1 / c2;
    const Pb = [a[0] + b2 * v[0], a[1] + b2 * v[1]];
    return rhumbDistance(p, Pb, { units: "degrees" });
}
function dot(u, v) {
    return u[0] * v[0] + u[1] * v[1];
}
function rhumbDistance(from, to, options = {}) {
    const origin = getCoord(from);
    const destination = getCoord(to);
    destination[0] += destination[0] - origin[0] > 180 ? -360 : origin[0] - destination[0] > 180 ? 360 : 0;
    const distanceInMeters = calculateRhumbDistance(origin, destination);
    const distance = convertLength(distanceInMeters, "meters", options.units);
    return distance;
}
function calculateRhumbDistance(origin, destination, radius) {
    radius = radius === void 0 ? earthRadius : Number(radius);
    const R = radius;
    const phi1 = origin[1] * Math.PI / 180;
    const phi2 = destination[1] * Math.PI / 180;
    const DeltaPhi = phi2 - phi1;
    let DeltaLambda = Math.abs(destination[0] - origin[0]) * Math.PI / 180;
    if (DeltaLambda > Math.PI) {
        DeltaLambda -= 2 * Math.PI;
    }
    const DeltaPsi = Math.log(
        Math.tan(phi2 / 2 + Math.PI / 4) / Math.tan(phi1 / 2 + Math.PI / 4)
    );
    const q = Math.abs(DeltaPsi) > 1e-11 ? DeltaPhi / DeltaPsi : Math.cos(phi1);
    const delta = Math.sqrt(
        DeltaPhi * DeltaPhi + q * q * DeltaLambda * DeltaLambda
    );
    const dist = delta * R;
    return dist;
}

export {
    pointToLineDistance,
    point,
    lineString
};