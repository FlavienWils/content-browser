<?php

declare(strict_types=1);

namespace Netgen\ContentBrowser\Tests\Item\ColumnProvider;

use Netgen\ContentBrowser\Config\Configuration;
use Netgen\ContentBrowser\Item\ColumnProvider\ColumnProvider;
use Netgen\ContentBrowser\Item\Renderer\ItemRendererInterface;
use Netgen\ContentBrowser\Tests\Stubs\ColumnValueProvider;
use Netgen\ContentBrowser\Tests\Stubs\InvalidColumnValueProvider;
use Netgen\ContentBrowser\Tests\Stubs\Item;
use PHPUnit\Framework\TestCase;

final class ColumnProviderTest extends TestCase
{
    /**
     * @var \Netgen\ContentBrowser\Item\Renderer\ItemRendererInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $itemRendererMock;

    /**
     * @var \Netgen\ContentBrowser\Config\ConfigurationInterface
     */
    private $config;

    /**
     * @var \Netgen\ContentBrowser\Item\ColumnProvider\ColumnProvider
     */
    private $columnProvider;

    public function setUp(): void
    {
        $this->itemRendererMock = $this->createMock(ItemRendererInterface::class);

        $this->config = new Configuration(
            'value',
            'Value',
            [
                'columns' => [
                    'column1' => [
                        'value_provider' => 'provider',
                    ],
                    'column2' => [
                        'value_provider' => 'invalid',
                    ],
                ],
            ]
        );

        $this->columnProvider = new ColumnProvider(
            $this->itemRendererMock,
            $this->config,
            [
                'provider' => new ColumnValueProvider(),
                'invalid' => new InvalidColumnValueProvider(),
            ]
        );
    }

    /**
     * @covers \Netgen\ContentBrowser\Item\ColumnProvider\ColumnProvider::__construct
     * @covers \Netgen\ContentBrowser\Item\ColumnProvider\ColumnProvider::provideColumn
     * @covers \Netgen\ContentBrowser\Item\ColumnProvider\ColumnProvider::provideColumns
     */
    public function testProvideColumns(): void
    {
        $this->assertSame(
            ['column1' => 'some_value', 'column2' => ''],
            $this->columnProvider->provideColumns(new Item())
        );
    }

    /**
     * @covers \Netgen\ContentBrowser\Item\ColumnProvider\ColumnProvider::provideColumn
     * @covers \Netgen\ContentBrowser\Item\ColumnProvider\ColumnProvider::provideColumns
     */
    public function testProvideColumnsWithTemplate(): void
    {
        $this->config = new Configuration(
            'value',
            'Value',
            [
                'columns' => [
                    'column' => [
                        'template' => 'template.html.twig',
                    ],
                ],
            ]
        );

        $this->columnProvider = new ColumnProvider(
            $this->itemRendererMock,
            $this->config,
            []
        );

        $item = new Item();

        $this->itemRendererMock
            ->expects($this->once())
            ->method('renderItem')
            ->with($this->identicalTo($item), $this->identicalTo('template.html.twig'))
            ->will($this->returnValue('rendered column'));

        $this->assertSame(
            ['column' => 'rendered column'],
            $this->columnProvider->provideColumns($item)
        );
    }

    /**
     * @covers \Netgen\ContentBrowser\Item\ColumnProvider\ColumnProvider::provideColumn
     * @covers \Netgen\ContentBrowser\Item\ColumnProvider\ColumnProvider::provideColumns
     * @expectedException \Netgen\ContentBrowser\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage Column value provider "provider" does not exist
     */
    public function testProvideColumnsThrowsInvalidArgumentExceptionWithNoProvider(): void
    {
        $this->columnProvider = new ColumnProvider(
            $this->itemRendererMock,
            $this->config,
            ['other' => new ColumnValueProvider()]
        );

        $this->columnProvider->provideColumns(new Item());
    }
}
