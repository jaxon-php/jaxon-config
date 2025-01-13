<?php

namespace Jaxon\Config\Tests\TestConfig;

use Jaxon\Config\Config;
use Jaxon\Config\ConfigReader;
use Jaxon\Config\ConfigSetter;
use Jaxon\Config\Exception\DataDepth;
use Jaxon\Config\Exception\FileAccess;
use Jaxon\Config\Exception\FileContent;
use Jaxon\Config\Exception\FileExtension;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    /**
     * @var ConfigReader
     */
    protected $xConfigReader;

    /**
     * @var ConfigSetter
     */
    protected $xConfigSetter;

    /**
     * @var Config
     */
    protected $xConfig;

    /**
     * @var string
     */
    protected $sConfigDir;

    protected function setUp(): void
    {
        $this->sConfigDir = __DIR__ .  '/../config';
        $this->xConfigSetter = new ConfigSetter();
        $this->xConfigReader = new ConfigReader($this->xConfigSetter);
        $this->xConfig = $this->xConfigSetter->newConfig(['core' => ['language' => 'en']]);
        $this->xConfig = $this->xConfigSetter->setOption($this->xConfig, 'core.prefix.function', 'jaxon_');
    }

    public function testPhpConfigReader()
    {
        $this->xConfig = $this->xConfigReader->load($this->xConfig, "{$this->sConfigDir}/config.php", 'jaxon');
        $this->assertEquals('en', $this->xConfig->getOption('core.language'));
        $this->assertEquals('jaxon_', $this->xConfig->getOption('core.prefix.function'));
        $this->assertFalse($this->xConfig->getOption('core.debug.on'));
        $this->assertFalse($this->xConfig->hasOption('core.debug.off'));
    }

    public function testYamlConfigReader()
    {
        $this->xConfig = $this->xConfigReader->load($this->xConfig, "{$this->sConfigDir}/config.yaml", 'jaxon');
        $this->assertEquals('en', $this->xConfig->getOption('core.language'));
        $this->assertEquals('jaxon_', $this->xConfig->getOption('core.prefix.function'));
        $this->assertFalse($this->xConfig->getOption('core.debug.on'));
        $this->assertFalse($this->xConfig->hasOption('core.debug.off'));
    }

    public function testJsonConfigReader()
    {
        $this->xConfig = $this->xConfigReader->load($this->xConfig, "{$this->sConfigDir}/config.json", 'jaxon');
        $this->assertEquals('en', $this->xConfig->getOption('core.language'));
        $this->assertEquals('jaxon_', $this->xConfig->getOption('core.prefix.function'));
        $this->assertFalse($this->xConfig->getOption('core.debug.on'));
        $this->assertFalse($this->xConfig->hasOption('core.debug.off'));
    }

    public function testReadOptionNames()
    {
        $this->xConfig = $this->xConfigReader->load($this->xConfig, "{$this->sConfigDir}/config.json");
        $aOptionNames = $this->xConfig->getOptionNames('jaxon.core');
        $this->assertIsArray($aOptionNames);
        $this->assertCount(3, $aOptionNames);
    }

    public function testSimpleArrayValues()
    {
        $this->xConfig = $this->xConfigReader->load($this->xConfig, "{$this->sConfigDir}/array.php");
        $aOption = $this->xConfig->getOption('core.array');
        $this->assertIsArray($aOption);
        $this->assertCount(4, $aOption);
        $this->assertEmpty($this->xConfig->getOptionNames('jaxon.array'));
    }

    public function testSetOptionsError()
    {
        // The key is missing
        $aOptions = ['core' => []];
        $this->xConfig = $this->xConfigSetter->setOptions($this->xConfig, $aOptions, '', 'core.missing');
        $this->assertFalse($this->xConfig->changed());
        // The value under the key is not an array
        $aOptions = ['core' => ['string' => 'String']];
        $this->xConfig = $this->xConfigSetter->setOptions($this->xConfig, $aOptions, '', 'core.string');
        $this->assertFalse($this->xConfig->changed());
        $this->assertFalse($this->xConfig->hasOption('core.string'));
    }

    public function testSetOptionsDataDepth()
    {
        $this->expectException(DataDepth::class);
        $this->xConfigSetter->setOptions($this->xConfig, [
            'core' => [
                'one' => [
                    'two' => [
                        'three' => [
                            'four' => [
                                'five' => [
                                    'six' => [
                                        'seven' => [
                                            'eight' => [
                                                'nine' => [
                                                    'ten' => [
                                                        'param' => 'Value',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function testEmptyFileName()
    {
        $this->assertEmpty($this->xConfigReader->read(''));
    }

    public function testMissingPhpFile()
    {
        $this->expectException(FileAccess::class);
        $this->xConfig = $this->xConfigReader->load($this->xConfig, "{$this->sConfigDir}/missing.php");
    }

    public function testMissingJsonFile()
    {
        $this->expectException(FileAccess::class);
        $this->xConfig = $this->xConfigReader->load($this->xConfig, "{$this->sConfigDir}/missing.json");
    }

    public function testMissingYamlFile()
    {
        $this->expectException(FileAccess::class);
        $this->xConfig = $this->xConfigReader->load($this->xConfig, "{$this->sConfigDir}/missing.yml");
    }

    public function testErrorInPhpFile()
    {
        $this->expectException(FileContent::class);
        $this->xConfig = $this->xConfigReader->load($this->xConfig, "{$this->sConfigDir}/error.php");
    }

    public function testErrorInJsonFile()
    {
        $this->expectException(FileContent::class);
        $this->xConfig = $this->xConfigReader->load($this->xConfig, "{$this->sConfigDir}/error.json");
    }

    public function testErrorInYamlFile()
    {
        $this->expectException(FileContent::class);
        $this->xConfig = $this->xConfigReader->load($this->xConfig, "{$this->sConfigDir}/error.yml");
    }

    public function testUnsupportedFileExtension()
    {
        $this->expectException(FileExtension::class);
        $this->xConfig = $this->xConfigReader->load($this->xConfig, "{$this->sConfigDir}/config.ini");
    }
}
