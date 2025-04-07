<?php
declare(strict_types=1);

namespace ThreeDViewer;

use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Module\AbstractModule;
use Omeka\Mvc\Controller\Plugin\Messenger;
use Omeka\Stdlib\Message;
use ThreeDViewer\Form\ConfigForm;

/**
 * Main class for the 3DViewer module.
 */
class Module extends AbstractModule
{
    /**
     * Retrieve the configuration array.
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Execute logic when the module is installed.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $messenger = new Messenger();
        $message = new Message("ThreeDViewer module installed.");
        $messenger->addSuccess($message);

        // Register 3D file types
        $this->updateWhitelist($serviceLocator);
    }
    
    /**
     * Register 3D file types in Omeka settings
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    protected function updateWhitelist(ServiceLocatorInterface $serviceLocator): void
    {
        $settings = $serviceLocator->get('Omeka\Settings');

        $whitelist = $settings->get('media_type_whitelist', []);
        $whitelist = array_values(array_unique(array_merge(array_values($whitelist), [
            'application/octet-stream',
            'model/stl',
            'model/gltf+json',
            'model/gltf-binary',
        ])));
        $settings->set('media_type_whitelist', $whitelist);

        $whitelist = $settings->get('extension_whitelist', []);
        $whitelist = array_values(array_unique(array_merge(array_values($whitelist), [
            'stl',
            'glb',
            'gltf',
        ])));
        $settings->set('extension_whitelist', $whitelist);
    }

    /**
     * Execute logic when the module is uninstalled.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $messenger = new Messenger();
        $message = new Message("ThreeDViewer module uninstalled.");
        $messenger->addWarning($message);
    }
   
    /**
     * Get the configuration form for this module.
     *
     * @param PhpRenderer $renderer
     * @return string
     */
    public function getConfigForm(PhpRenderer $renderer)
    {
        $services = $this->getServiceLocator();
        $config = $services->get('Config');
        $settings = $services->get('Omeka\Settings');
        
        $form = new ConfigForm;
        $form->init();
        
        $form->setData([
            'threedviewer_viewer_height' => $settings->get('threedviewer_viewer_height', 500),
            'threedviewer_auto_rotate' => $settings->get('threedviewer_auto_rotate', true),
            'threedviewer_foreground_color' => $settings->get('threedviewer_foreground_color', '#0000FF'),
            'threedviewer_background_color' => $settings->get('threedviewer_background_color', '#b5b5b5'),
            'threedviewer_show_grid' => $settings->get('threedviewer_show_grid', false),
        ]);
        
        return $renderer->formCollection($form, false);
    }
    
    /**
     * Handle the configuration form submission.
     *
     * @param AbstractController $controller
     */
    public function handleConfigForm(AbstractController $controller)
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        
        $config = $controller->params()->fromPost();
        
        $settings->set('threedviewer_viewer_height', $config['threedviewer_viewer_height']);
        $settings->set('threedviewer_auto_rotate', $config['threedviewer_auto_rotate'] === '1');
        $settings->set('threedviewer_foreground_color', $config['threedviewer_foreground_color']);
        $settings->set('threedviewer_background_color', $config['threedviewer_background_color']);
        $settings->set('threedviewer_show_grid', $config['threedviewer_show_grid'] === '1');
    }
}
