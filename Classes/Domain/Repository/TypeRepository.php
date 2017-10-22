<?php
namespace KN\Operations\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Karsten Nowak <captnnowi@gmx.de>
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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 *
 *
 * @package operations
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class TypeRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

	/**
	* default ordering
	* 
	* @return array
	*/
	protected $defaultOrderings = array( 
	    'title' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING, 
	); 
	
	public function findAll() {
		$query = $this->createQuery();
		return $query->execute();
	}
	public function findAllTypesWithTitle()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_operations_domain_model_type');
        $result = $queryBuilder
            ->select('title')
            ->from('tx_operations_domain_model_type')
            ->execute()->fetchAll();
        return $result;
        /** @var TYPE_NAME $result */
//        DebuggerUtility::var_dump($result, __METHOD__);
    }


}