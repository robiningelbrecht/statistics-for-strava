<?php

namespace App\Tests\Infrastructure\CQRS\Command\Deserialize;

use App\Infrastructure\CQRS\Command\Deserialize\CommandDeserializer;
use App\Infrastructure\CQRS\Command\Deserialize\CouldNotDeserializeCommand;
use App\Infrastructure\Serialization\Json;
use App\Tests\Infrastructure\CQRS\Command\Bus\RunAnOperation\RunAnOperation;
use PHPUnit\Framework\TestCase;

class CommandDeserializerTest extends TestCase
{
    private CommandDeserializer $commandDeserializer;

    public function testDeserialize(): void
    {
        $command = $this->commandDeserializer->deserialize(Json::encode([
            'commandName' => TestDeserializableCommand::class,
            'payload' => [
                'message' => 'Hello',
                'url' => 'https://example.com',
            ],
        ]));

        $this->assertInstanceOf(TestDeserializableCommand::class, $command);
        $this->assertEquals('Hello', $command->getMessage());
        $this->assertEquals('https://example.com', (string) $command->getUrl());
    }

    public function testDeserializeItShouldRoundTrip(): void
    {
        $original = TestDeserializableCommand::fromPayload([
            'message' => 'Hello',
            'url' => 'https://example.com',
        ]);

        $deserialized = $this->commandDeserializer->deserialize(Json::encode($original));

        $this->assertEquals($original, $deserialized);
    }

    public function testDeserializeWithInvalidPayload(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload());

        $this->commandDeserializer->deserialize(Json::encode(['no' => 'command']));
    }

    public function testDeserializeWithUnknownCommand(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::unknownCommand('App\Does\Not\Exist'));

        $this->commandDeserializer->deserialize(Json::encode([
            'commandName' => 'App\Does\Not\Exist',
            'payload' => [],
        ]));
    }

    public function testDeserializeWithCommandThatIsNotDeserializable(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::notDeserializable(RunAnOperation::class));

        $this->commandDeserializer->deserialize(Json::encode([
            'commandName' => RunAnOperation::class,
            'payload' => [],
        ]));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandDeserializer = new CommandDeserializer();
    }
}
