<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class MesIndexCompteur extends eqLogic {
    public static $_widgetPossibility = array('custom' => array(
        'visibility' => true,
        'displayName' => array('dashboard' => true, 'view' => true),
        'optionalParameters' => true,
    ));

    public function postSave() {
        $unit = '';
        switch ($this->getConfiguration('typeCompteur')) {
            case "Electricite":
                $unit="kWh";
                break;
            case "Eau":
            case "Gaz":
                $unit="m3";
                break;
        }

        $lastIndexCmd = $this->getCmd(null, 'LastIndex');
        if (!is_object($lastIndexCmd)) {
            $lastIndexCmd = new MesIndexCompteurCmd();
            $lastIndexCmd->setName(__('Index Courant', __FILE__));
            $lastIndexCmd->setIsHistorized(1);
        }

        $lastIndexCmd->setEqLogic_id($this->getId());
        $lastIndexCmd->setLogicalId('LastIndex');
        $lastIndexCmd->setType('info');
        $lastIndexCmd->setSubType('numeric');
        $lastIndexCmd->setUnite($unit);
        $lastIndexCmd->save();

        $deltaIndexCmd = $this->getCmd(null, 'DeltaIndex');
        if (!is_object($deltaIndexCmd)) {
            $deltaIndexCmd = new MesIndexCompteurCmd();
            $deltaIndexCmd->setName(__('Index Delta', __FILE__));
            $deltaIndexCmd->setIsHistorized(1);
        }

        $deltaIndexCmd->setEqLogic_id($this->getId());
        $deltaIndexCmd->setLogicalId('DeltaIndex');
        $deltaIndexCmd->setType('info');
        $deltaIndexCmd->setSubType('numeric');
        $deltaIndexCmd->setUnite($unit);
        $deltaIndexCmd->save();


        $enterNewIndex = $this->getCmd(null, 'NewIndex');
        if (!is_object($enterNewIndex)) {
            $enterNewIndex = new MesIndexCompteurCmd();
            $enterNewIndex->setName(__('Nouvel Index', __FILE__));
        }

        $enterNewIndex->setEqLogic_id($this->getId());
        $enterNewIndex->setLogicalId('NewIndex');
        $enterNewIndex->setType('action');
        $enterNewIndex->setSubType('other');
        $enterNewIndex->save();
    }

    public function toHtml($_version = 'dashboard') {
        //cache::delete('widgetHtml' . $_version . $this->getId());
        $replace = $this->preToHtml($_version);
        if (!is_array($replace)) {
            log::add(__CLASS__, 'debug', 'Not array');
            return $replace;
        }
     
        $lastIndexCmd = $this->getCmd(null, 'LastIndex'); 
        $deltaIndexCmd = $this->getCmd(null, 'DeltaIndex');
        $enterNewIndex = $this->getCmd(null, 'NewIndex');
        $version = jeedom::versionAlias($_version);
        $currentIndex =  $lastIndexCmd->execCmd();
        if($currentIndex == '' || $currentIndex == null)
        {
            $currentIndex = 0;
        }

        $deltaIndex =  $deltaIndexCmd->execCmd();
        if($deltaIndex == '' || $deltaIndex == null)
        {
            $deltaIndex = 0;
        }

        $replace['#CurrentIndex#'] = $currentIndex;
        $replace['#DeltaIndex#'] = round($deltaIndex, 3);
        $replace['#CurrentIndexUnitCode#'] = $lastIndexCmd->getUnite();
        $replace['#EncodeNewIndex#'] = $enterNewIndex->getId();

        $calculatedPrice = 0;
        $subscriptionPrice = $this->getConfiguration('subscriptionPrice');
        $unitPrice = $this->getConfiguration('unitPrice');
        if($subscriptionPrice != null && $subscriptionPrice != '' && $unitPrice != null && $unitPrice != '')
        {
            $calculatedPrice = $subscriptionPrice + max(($unitPrice * $deltaIndex), 0);
        }
        
        $replace['#calculatedPrice#'] = round($calculatedPrice, 3);

		$html = template_replace($replace, getTemplate('core', $version, 'MesIndexCompteur', 'MesIndexCompteur'));
        //cache::set('widgetHtml' . $_version . $this->getId(), $html, 0);
		return $html;
    }
}

class MesIndexCompteurCmd extends cmd {
    public function execute($_options = array()) {
        if ($this->getType() == 'info') {
            return;
        }

        $eqLogic = $this->getEqLogic();
        if ($this->getLogicalId() == 'NewIndex') {
            log::add('MesIndexCompteur', 'debug', $_options['val']);

            $lastIndexCmd = $eqLogic->getCmd(null, 'LastIndex'); 
            $newIndex = $_options['val'];
            $lastIndexCmd->event($newIndex);

            $deltaIndexCmd = $eqLogic->getCmd(null, 'DeltaIndex');
            // Reference
            if($_options['referenceIndex'] == 1)
            {
              $eqLogic->setConfiguration('lastIndex', $newIndex);
              $eqLogic->save();
            }

            $lastIndex = $eqLogic->getConfiguration('lastIndex');
            if($lastIndex == '')
            {
                $lastIndex = 0;
            }

            $deltaIndexCmd->event($newIndex - $lastIndex);

            $eqLogic->refreshWidget();
        }
    }
}