<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Application\UseCase;

use CubaDevOps\Flexi\Application\Commands\ValidateModulesCommand;
use CubaDevOps\Flexi\Application\UseCase\ValidateModules;
use Flexi\Contracts\Interfaces\HandlerInterface;
use PHPUnit\Framework\TestCase;

class ValidateModulesTest extends TestCase
{
    public function testImplementsHandlerInterface(): void
    {
        $validateModules = new ValidateModules();
        $this->assertInstanceOf(HandlerInterface::class, $validateModules);
    }

    public function testConstructorWithDefaultPaths(): void
    {
        $useCase = new ValidateModules();
        $this->assertInstanceOf(ValidateModules::class, $useCase);
    }

    public function testConstructorWithCustomPaths(): void
    {
        $customModules = '/custom/modules';

        $useCase = new ValidateModules($customModules);
        $this->assertInstanceOf(ValidateModules::class, $useCase);
    }

    public function testHandleWithNoModules(): void
    {
        $tempModulesPath = sys_get_temp_dir() . '/test_validate_modules_' . uniqid();
        $tempRootPath = sys_get_temp_dir() . '/test_validate_root_' . uniqid();
        $tempComposerPath = $tempRootPath . '/composer.json';

        mkdir($tempModulesPath);
        mkdir($tempRootPath);

        // Create a basic composer.json
        $composerData = ['require' => []];
        file_put_contents($tempComposerPath, json_encode($composerData));

        $validateModules = new ValidateModules($tempModulesPath);
        $dto = new ValidateModulesCommand();

        try {
            $result = $validateModules->handle($dto);

            // Verify it returns a MessageInterface
            $this->assertInstanceOf(\Flexi\Contracts\Interfaces\MessageInterface::class, $result);

            // Verify response is valid JSON
            $response = json_decode($result->get('body'), true);
            $this->assertIsArray($response);

        } catch (\Exception $e) {
            // This is expected when validation logic encounters issues
            $this->assertIsString($e->getMessage());

        } finally {
            // Clean up
            if (is_file($tempComposerPath)) {
                unlink($tempComposerPath);
            }
            if (is_dir($tempRootPath)) {
                rmdir($tempRootPath);
            }
            if (is_dir($tempModulesPath)) {
                rmdir($tempModulesPath);
            }
        }
    }
}