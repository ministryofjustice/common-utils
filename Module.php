<?php

namespace CommonUtils;

use Zend\Mvc\MvcEvent;
use Zend\Mvc\ModuleRouteListener;
use Zend\Log\Filter\Priority;
use Sirius\Logging\Logger;
use Sirius\Logging\Extractor;
use Zend\Log\Logger as ZendLogger;


/**
 * Class Module
 * @package DateUtils
 */
class Module
{
    /**
     * @param MvcEvent $e
     */
    public function onBootstrap(MvcEvent $event)
    {
        $eventManager        = $event->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $extractor = $event->getApplication()->getServiceManager()->get('CommonUtils\Extractor');
        $extractor->setRequest($event->getRequest());
        $extractor->setResponse($event->getResponse());

        $logger = $event->getApplication()->getServiceManager()->get('Logger');
        $logger->setExtractor($extractor);

        //catches exceptions for stuff that is dispatched
        $sharedManager = $event->getApplication()->getEventManager()->getSharedManager();
        $sm = $event->getApplication()->getServiceManager();
        $sharedManager->attach('Zend\Mvc\Application', 'dispatch.error',
            function($event) use ($sm) {
            if ($event->getParam('exception')){
                $sm->get('Logger')->crit($event->getParam('exception'));
            }
        });

        //catch exceptions that are uncaught
        set_exception_handler(function($exception) use ($sm, $extractor, $event) {
                //ensure that the application log, logs a 500 error
                $event->getResponse()->setStatusCode(500);
                $extractor->setResponse($event->getResponse());
                http_response_code(500);
                $sm->get('Logger')->crit($exception);
        });
        return;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    /**
     * @return array
     */
    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'CommonUtils\SiriusLogger' => function ($sm) {
                    $config = $sm->get('Config')['CommonUtils\Logger'];
                    $logger = new Sirius\Logging\Logger();

                    foreach ($config['writers'] as $writer) {
                        if ($writer['enabled']) {
                            $writerAdapter = new $writer['adapter']($writer['adapterOptions']['output']);
                            $logger->addWriter($writerAdapter);

                            if(!empty($writer['formatter'])) {
                                $writerFormatter = new $writer['formatter']($writer['formatterOptions']);
                                $writerAdapter->setFormatter($writerFormatter);
                            }
                            $writerAdapter->addFilter(
                                new Priority(
                                    $writer['filter']
                                )
                            );
                        }
                    }

                    ZendLogger::registerErrorHandler($logger);
                    return $logger;
                },
                'CommonUtils\Extractor' => function($sm) {
                    $config = $sm->get('Config')['CommonUtils\Logger']['extractions'];
                    $extractor = new Sirius\Logging\Extractor($config);
                    return $extractor;
                 }

            )
        );
    }


}
