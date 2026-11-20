<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Yves\AgenticCommerce\Helper;

use Codeception\Module;
use Codeception\Stub;
use Codeception\TestInterface;
use DateTimeInterface;
use Generated\Shared\Transfer\AgenticCommercePrincipalCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use ReflectionClass;
use ReflectionProperty;
use Spryker\Client\Session\SessionClientInterface;
use Spryker\Service\Container\Container;
use Spryker\Yves\Kernel\AbstractPlugin;
use Spryker\Yves\Kernel\Controller\AbstractController;
use Spryker\Yves\Kernel\Widget\AbstractWidget;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeature\Yves\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Yves\AgenticCommerce\AgenticCommerceFactory;
use SprykerTest\Shared\Testify\Helper\ConfigHelperTrait;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Cmf\Component\Routing\ChainRouterInterface;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\Validator\Validation;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\TwigFilter;

class AgenticCommerceYvesHelper extends Module
{
    use ConfigHelperTrait;

    public const string ISSUER = 'https://glue.shop.test';

    public const string AUTHORIZATION_ENDPOINT = 'https://shop.test/connect/authorize';

    public const string AUTHORIZATION_ENDPOINT_HOST = 'shop.test';

    public const string RESOURCE_URI = self::ISSUER . '/agentic-commerce';

    public const string PUBLIC_KEY = 'public-key';

    protected const string PRINCIPAL_VALUE_SEPARATOR = ':';

    protected const string TWIG_NAMESPACE = 'AgenticCommerce';

    protected const string THEME_DIRECTORY = __DIR__ . '/../../../../../../src/SprykerFeature/Yves/AgenticCommerce/Theme/default';

    protected const string FORM_LAYOUT = 'form_div_layout.html.twig';

    protected const string TWIG_FILTER_TRANS = 'trans';

    protected const string TWIG_FILTER_MONEY = 'money';

    protected const string TWIG_FILTER_FORMAT_DATE_TIME = 'formatDateTime';

    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i';

    protected const string SERVICE_FLASH_MESSENGER = 'flash_messenger';

    protected const string SERVICE_LOCALE = 'locale';

    protected const string SERVICE_ROUTER = 'routers';

    protected const string LOCALE = 'en_US';

    /**
     * @var array<string, mixed>
     */
    protected array $sessionData = [];

    /**
     * @var list<string>
     */
    protected array $successMessages = [];

    /**
     * @var list<string>
     */
    protected array $errorMessages = [];

    protected ?CsrfTokenManager $csrfTokenManager = null;

    public function _before(TestInterface $test): void
    {
        $this->sessionData = [];
        $this->successMessages = [];
        $this->errorMessages = [];
        $this->csrfTokenManager = null;

        $this->getConfigHelper()->setConfig(AgenticCommerceConstants::ISSUER, static::ISSUER);
        $this->getConfigHelper()->setConfig(AgenticCommerceConstants::AUTHORIZATION_ENDPOINT, static::AUTHORIZATION_ENDPOINT);
        $this->getConfigHelper()->setConfig(AgenticCommerceConstants::PUBLIC_KEY, static::PUBLIC_KEY);
    }

    /**
     * @param array<string, mixed> $factoryMethodResults Factory `get*` method name to the value it returns.
     */
    public function createFactory(array $factoryMethodResults): AgenticCommerceFactory
    {
        return Stub::make(AgenticCommerceFactory::class, $factoryMethodResults + [
            'getSessionClient' => $this->createSessionClient(),
            'getRouter' => $this->createRouter(),
            'getFormFactory' => $this->createFormFactory(),
            'getConfig' => new AgenticCommerceConfig(),
        ]);
    }

    /**
     * @template T of \Spryker\Yves\Kernel\Controller\AbstractController
     *
     * @param class-string<T> $controllerClass
     *
     * @return T
     */
    public function createController(string $controllerClass, AgenticCommerceFactory $agenticCommerceFactory): AbstractController
    {
        $controller = $this->createWithFactory($controllerClass, $agenticCommerceFactory);
        $controller->setApplication(new Container([
            static::SERVICE_FLASH_MESSENGER => $this->createMessenger(),
            static::SERVICE_LOCALE => static::LOCALE,
            static::SERVICE_ROUTER => $agenticCommerceFactory->getRouter(),
        ]));

        return $controller;
    }

    /**
     * @template T of \Spryker\Yves\Kernel\AbstractPlugin
     *
     * @param class-string<T> $pluginClass
     *
     * @return T
     */
    public function createPlugin(string $pluginClass, AgenticCommerceFactory $agenticCommerceFactory): AbstractPlugin
    {
        return $this->createWithFactory($pluginClass, $agenticCommerceFactory);
    }

    /**
     * @template T of \Spryker\Yves\Kernel\Widget\AbstractWidget
     *
     * @param class-string<T> $widgetClass
     * @param list<mixed> $arguments
     *
     * @return T
     */
    public function createWidget(string $widgetClass, AgenticCommerceFactory $agenticCommerceFactory, array $arguments): AbstractWidget
    {
        $widget = (new ReflectionClass($widgetClass))->newInstanceWithoutConstructor();
        $widget->setFactory($agenticCommerceFactory);
        $widget->__construct(...$arguments);

        return $widget;
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     */
    public function createRequest(array $query = [], array $body = [], string $host = self::AUTHORIZATION_ENDPOINT_HOST): Request
    {
        $request = new Request($query, $body, [], [], [], ['HTTP_HOST' => $host, 'HTTPS' => 'on']);

        if ($body !== []) {
            $request->setMethod(Request::METHOD_POST);
        }

        return $request;
    }

    /**
     * @param list<array{0: string, 1: string}> $principals Pairs of principal type and principal reference.
     */
    public function createPrincipalCollection(array $principals): AgenticCommercePrincipalCollectionTransfer
    {
        $agenticCommercePrincipalCollectionTransfer = new AgenticCommercePrincipalCollectionTransfer();

        foreach ($principals as [$principalType, $principalReference]) {
            $agenticCommercePrincipalCollectionTransfer->addPrincipal(
                (new AgenticCommercePrincipalTransfer())
                    ->setPrincipalType($principalType)
                    ->setPrincipalReference($principalReference)
                    ->setDisplayName(sprintf('%s %s', $principalType, $principalReference)),
            );
        }

        return $agenticCommercePrincipalCollectionTransfer;
    }

    public function createPrincipalValue(string $principalType, string $principalReference): string
    {
        return $principalType . static::PRINCIPAL_VALUE_SEPARATOR . $principalReference;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function renderTemplateWithGlossaryKeys(string $templateName, array $context): string
    {
        $filesystemLoader = new FilesystemLoader([static::getFormLayoutDirectory()]);
        $filesystemLoader->addPath(static::THEME_DIRECTORY, static::TWIG_NAMESPACE);
        $environment = new Environment($filesystemLoader, ['strict_variables' => true]);
        $environment->addExtension(new FormExtension());
        $environment->addRuntimeLoader(new FactoryRuntimeLoader([
            FormRenderer::class => static fn (): FormRenderer => new FormRenderer(new TwigRendererEngine([static::FORM_LAYOUT], $environment)),
        ]));
        $environment->addFilter(new TwigFilter(
            static::TWIG_FILTER_TRANS,
            static fn (string $glossaryKey, array $parameters = []): string => implode(' ', [$glossaryKey, ...array_values($parameters)]),
        ));
        $environment->addFilter(new TwigFilter(
            static::TWIG_FILTER_MONEY,
            static fn (int $amount, bool $withSymbol = true, ?string $currencyCode = null): string => sprintf('%d %s', $amount, $currencyCode),
        ));
        $environment->addFilter(new TwigFilter(
            static::TWIG_FILTER_FORMAT_DATE_TIME,
            static fn (DateTimeInterface $dateTime): string => $dateTime->format(static::DATE_TIME_FORMAT),
        ));

        return $environment->render($templateName, $context);
    }

    public function createSessionClient(): SessionClientInterface
    {
        return Stub::makeEmpty(SessionClientInterface::class, [
            'get' => fn (string $name, mixed $default = null): mixed => $this->sessionData[$name] ?? $default,
            'set' => function (string $name, mixed $value): void {
                $this->sessionData[$name] = $value;
            },
            'remove' => function (string $name): mixed {
                $value = $this->sessionData[$name] ?? null;
                unset($this->sessionData[$name]);

                return $value;
            },
            'has' => fn (string $name): bool => array_key_exists($name, $this->sessionData),
        ]);
    }

    public function setSessionValue(string $name, mixed $value): void
    {
        $this->sessionData[$name] = $value;
    }

    public function getSessionValue(string $name): mixed
    {
        return $this->sessionData[$name] ?? null;
    }

    public function getCsrfToken(string $formName): string
    {
        return $this->getCsrfTokenManager()->getToken($formName)->getValue();
    }

    /**
     * @return list<string>
     */
    public function getSuccessMessages(): array
    {
        return $this->successMessages;
    }

    /**
     * @return list<string>
     */
    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return T
     */
    protected function createWithFactory(string $className, AgenticCommerceFactory $agenticCommerceFactory): object
    {
        $instance = new $className();
        (new ReflectionProperty($instance, 'factory'))->setValue($instance, $agenticCommerceFactory);

        return $instance;
    }

    protected static function getFormLayoutDirectory(): string
    {
        return dirname((string)(new ReflectionClass(FormExtension::class))->getFileName(), 2) . '/Resources/views/Form';
    }

    protected function createRouter(): ChainRouterInterface
    {
        return Stub::makeEmpty(ChainRouterInterface::class, [
            'generate' => static fn (string $name, array $parameters = []): string => $parameters === []
                ? sprintf('/%s', $name)
                : sprintf('/%s?%s', $name, http_build_query($parameters)),
        ]);
    }

    protected function createFormFactory(): FormFactoryInterface
    {
        return Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->addExtension(new CsrfExtension($this->getCsrfTokenManager()))
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory();
    }

    protected function getCsrfTokenManager(): CsrfTokenManager
    {
        if ($this->csrfTokenManager !== null) {
            return $this->csrfTokenManager;
        }

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $this->csrfTokenManager = new CsrfTokenManager(null, new SessionTokenStorage($requestStack));

        return $this->csrfTokenManager;
    }

    protected function createMessenger(): object
    {
        $helper = $this;

        return new class ($helper) {
            public function __construct(protected AgenticCommerceYvesHelper $helper)
            {
            }

            public function addSuccessMessage(string $message): void
            {
                $this->helper->recordSuccessMessage($message);
            }

            public function addInfoMessage(string $message): void
            {
            }

            public function addErrorMessage(string $message): void
            {
                $this->helper->recordErrorMessage($message);
            }
        };
    }

    public function recordSuccessMessage(string $message): void
    {
        $this->successMessages[] = $message;
    }

    public function recordErrorMessage(string $message): void
    {
        $this->errorMessages[] = $message;
    }
}
