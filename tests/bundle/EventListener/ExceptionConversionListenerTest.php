<?php

namespace Netgen\Bundle\ContentBrowserBundle\Tests\EventListener;

use Exception;
use Netgen\Bundle\ContentBrowserBundle\EventListener\ExceptionConversionListener;
use Netgen\Bundle\ContentBrowserBundle\EventListener\SetIsApiRequestListener;
use Netgen\ContentBrowser\Exceptions\InvalidArgumentException;
use Netgen\ContentBrowser\Exceptions\NotFoundException;
use Netgen\ContentBrowser\Exceptions\OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class ExceptionConversionListenerTest extends TestCase
{
    /**
     * @var \Netgen\Bundle\ContentBrowserBundle\EventListener\ExceptionConversionListener
     */
    private $eventListener;

    public function setUp()
    {
        $this->eventListener = new ExceptionConversionListener();
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\ExceptionConversionListener::getSubscribedEvents
     */
    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [KernelEvents::EXCEPTION => ['onException', 10]],
            $this->eventListener->getSubscribedEvents()
        );
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\ExceptionConversionListener::onException
     * @dataProvider onExceptionDataProvider
     *
     * @param \Exception $exception
     * @param string $convertedClass
     * @param int $statusCode
     * @param bool $converted
     */
    public function testOnException($exception, $convertedClass, $statusCode, $converted)
    {
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $request->attributes->set(SetIsApiRequestListener::API_FLAG_NAME, true);

        $event = new GetResponseForExceptionEvent(
            $kernelMock,
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );

        $this->eventListener->onException($event);

        $this->assertInstanceOf(
            $convertedClass,
            $event->getException()
        );

        $this->assertEquals($exception->getMessage(), $event->getException()->getMessage());
        $this->assertEquals($exception->getCode(), $event->getException()->getCode());

        if ($event->getException() instanceof HttpExceptionInterface) {
            $this->assertEquals($statusCode, $event->getException()->getStatusCode());
        }

        $converted ?
            $this->assertEquals($exception, $event->getException()->getPrevious()) :
            $this->assertNull($event->getException()->getPrevious());
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\ExceptionConversionListener::onException
     */
    public function testOnExceptionNotConvertsOtherExceptions()
    {
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $request->attributes->set(SetIsApiRequestListener::API_FLAG_NAME, true);
        $exception = new Exception('Some error');

        $event = new GetResponseForExceptionEvent(
            $kernelMock,
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );

        $this->eventListener->onException($event);

        $this->assertEquals($exception, $event->getException());
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\ExceptionConversionListener::onException
     */
    public function testOnExceptionInSubRequest()
    {
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $request->attributes->set(SetIsApiRequestListener::API_FLAG_NAME, true);
        $exception = new NotFoundException('Some error');

        $event = new GetResponseForExceptionEvent(
            $kernelMock,
            $request,
            HttpKernelInterface::SUB_REQUEST,
            $exception
        );

        $this->eventListener->onException($event);

        $this->assertEquals($exception, $event->getException());
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\ExceptionConversionListener::onException
     */
    public function testOnExceptionInNonAPIRequest()
    {
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $exception = new NotFoundException('Some error');

        $event = new GetResponseForExceptionEvent(
            $kernelMock,
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );

        $this->eventListener->onException($event);

        $this->assertEquals($exception, $event->getException());
    }

    public function onExceptionDataProvider()
    {
        return [
            [
                new NotFoundException('Some error'),
                NotFoundHttpException::class,
                Response::HTTP_NOT_FOUND,
                true,
            ],
            [
                new InvalidArgumentException('Some error'),
                BadRequestHttpException::class,
                Response::HTTP_BAD_REQUEST,
                true,
            ],
            [
                new OutOfBoundsException('Some error'),
                UnprocessableEntityHttpException::class,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                true,
            ],
            [
                new AccessDeniedException('Some error'),
                AccessDeniedHttpException::class,
                Response::HTTP_FORBIDDEN,
                true,
            ],
            [
                new AccessDeniedHttpException('Some error'),
                AccessDeniedHttpException::class,
                Response::HTTP_FORBIDDEN,
                false,
            ],
        ];
    }
}
