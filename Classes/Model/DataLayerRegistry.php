<?php
namespace Aoe\GoogleTagManager\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Aoe\GoogleTagManager\Service\VariableProviderInterface;
use Aoe\GoogleTagManager\ViewHelpers\DataLayerViewHelper;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

class DataLayerRegistry implements SingletonInterface
{
    /**
     * @var array
     */
    private $vars = array();

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function addVar($name, $value)
    {
        $this->vars[$name] = $value;
    }

    /**
     * @return array
     */
    public function getVars()
    {
        return array_merge($this->vars, $this->getVarsFromHooks());
    }

    /**
     * clear values
     */
    public function clear()
    {
        $this->vars = array();
    }

    /**
     * @return string
     */
    public function getJSVariables()
    {
        $dataLayerViewHelper = new DataLayerViewHelper();
        $js = '';
        foreach ($this->vars as $name => $value) {
            $js .= $dataLayerViewHelper->render($name, $value);
        }
        return $js;
    }

    /**
     * @return array
     */
    private function getVarsFromHooks()
    {
        $vars = array();
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['google_tag_manager']['variableProviders'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['google_tag_manager']['variableProviders'] as $class) {
                if (!class_exists($class)) {
                    throw new \RuntimeException(
                        'Registered hook class "' . $class . '" for Google Tag Manager variable provider does not exist.',
                        1459503274
                    );
                }
                /** @var VariableProviderInterface $variableProvider */
                $variableProvider = $this->objectManager->get($class);
                if (!$variableProvider instanceof VariableProviderInterface) {
                    throw new \RuntimeException(
                        'Hook "' . $class . '" for Google Tag Manager variable provider does not implement VariableProviderInterface.',
                        1459503275
                    );
                }
                $vars = array_merge($vars, $variableProvider->getVariables());
            }
        }
        return $vars;
    }
}