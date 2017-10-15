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
use Doctrine\Common\Util\Debug;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 *
 *
 * @package operations
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class OperationRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

	/**
	* default ordering
	*
	* @return array
	*/
	protected $defaultOrderings = array(
	    'begin' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING,
	);

	/**
	* Returns the objects of this repository matching the demand
	*
	* @param \KN\Operations\Domain\Model\OperationDemand $demand
	* @param array $settings
	* @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	*/
	public function findDemanded(\KN\Operations\Domain\Model\OperationDemand $demand, $settings) {
		$query = $this->generateQuery($demand, $settings);
		return $query->execute();
	}

    /**
	 * Counts all available operations without the limit
	 *
	 * @param integer $count
	 */
	public function countDemanded($demand) {
		return $this->findDemanded($demand, NULL)->count();
	}

    /**
     * Counts all available operations grouped by a property
     *
     * @param array $years
     * @param array $types
     * @return array
     */
    public function countGroupedByYearAndType($years,$types) {

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_operations_domain_model_operation');
        $result = $queryBuilder
            ->add('select','ot.title as title, COUNT(*) as count, FROM_UNIXTIME(o.begin, \'%Y\') as year')
            ->from('tx_operations_domain_model_type','ot')
            ->innerJoin('ot','tx_operations_operation_type_mm','type_mm','type_mm.uid_foreign = ot.uid')
            ->innerJoin('type_mm','tx_operations_domain_model_operation','o','type_mm.uid_local = o.uid')
            ->groupBy('year')
            ->addGroupBy('ot.uid')
            ->execute()->fetchAll();
//            ->execute();
//        debug($queryBuilder->getSQL());
        DebuggerUtility::var_dump($result,__METHOD__);
        DebuggerUtility::var_dump($types,'Typen:'.__METHOD__);

        return $result;
    }


    /**
     * Counts all available operations grouped by year
     *
     * @return array
     */
    public function countGroupedByYear() {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_operations_domain_model_operation');

        $statement = $queryBuilder
            ->add('select','COUNT(*) as count, FROM_UNIXTIME(begin, \'%Y\') as year',true)
            ->from('tx_operations_domain_model_operation')
            ->groupBy('year')
            ->orderBy('year',ASC)
            ->execute();
        $result = $statement->fetchAll();

        return $result;
    }


	/**
	 * Counts all available operations grouped by a property
	 *
     * @todo remove or clean up this function
     * @param string $property
	 * @param integer $count
     * @return array
	 */
	public function countGroupedBy($demand, $property) {
        $groupedCounted = [];

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_operations_domain_model_operation');
        $rows = $queryBuilder
            ->add('select','COUNT(*) as count, FROM_UNIXTIME(begin, \'%Y\') as year',true)
            ->from('tx_operations_domain_model_operation')
            ->groupBy('year')
            ->execute()->fetchAll();

        return $groupedCounted;
	}

	/**
	 * Generates the query
	 *
	 * @param \KN\Operations\Domain\Model\OperationDemand $demand
	 * @param array $settings
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 */
	protected function generateQuery(\KN\Operations\Domain\Model\OperationDemand $demand, $settings) {
		$query = $this->createQuery();
		//$query->getQuerySettings()->setRespectStoragePage(FALSE);

		$constraints = $this->createConstraintsFromDemand($query, $demand, $settings);
		if (!empty($constraints)) {
			$query->matching(
					$query->logicalAnd($constraints)
			);
		}
		$limit = $settings['limit'];
		if($limit<=0){
			$limit = 300;
		}
		if ($demand->getLimit() != NULL) {
			$query->setLimit((int) $demand->getLimit());
		} else {
			$query->setLimit((int) $limit);
		}
		return $query;
	}

	/**
	 * Returns an array of constraints created from a given demand object.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
	 * @param \KN\Operations\Domain\Model\OperationDemand $demand
	 * @param array $settings
	 * @return array<Tx_Extbase_Persistence_QOM_Constraint>
	 */
	protected function createConstraintsFromDemand(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query, \KN\Operations\Domain\Model\OperationDemand $demand, $settings) {

		$constraints = array();

        // begin
		$fromTimestamp = mktime(0,0,0,1,1,$demand->getBegin());
		$toTimestamp = mktime(23,59,59,12,31,$demand->getBegin());
		if($demand->getBegin()) {
			$constraints[] = $query->logicalAnd(
				$query->greaterThanOrEqual('begin', $fromTimestamp),
				$query->lessThanOrEqual('begin', $toTimestamp)
			);
		}
        // type
		if($demand->getType()){
			$constraints[] = $query->contains('type',$demand->getType());
		}
        // search
		if(!empty($demand->getSearchString())){
            $searchSubject = $demand->getSearchstring();
            $searchFields = GeneralUtility::trimExplode(',', $settings['searchFields'], true);
            $searchConstraints = [];
            if (count($searchFields) === 0) {
                throw new \UnexpectedValueException('No search fields in TypoScript setup defined', 1506861158);
            }
            foreach ($searchFields as $field) {
                if (!empty($searchSubject)) {
                    $searchConstraints[] = $query->like($field, '%' . $searchSubject . '%');
                }
            }
            if (count($searchConstraints)) {
                $constraints[] = $query->logicalOr($searchConstraints);
            }
        }

        // map constraints
        if($settings['showMap']) {
			$constraints[] = $query->logicalAnd(
				$query->greaterThan('latitude',0),
				$query->greaterThan('longitude',0)
			);
		}

		$constraints = $this->cleanUnusedConstaints($constraints);

		return $constraints;
	}

	/**
	 *  Clean not used constraints
	 *
	 * @param array $contrains
	 * @return array
	 */

	protected function cleanUnusedConstaints($constraints){
		foreach ($constraints as $key => $value) {
			if (is_null($value)) {
				unset($constraints[$key]);
			}
		}
		return $constraints;
	}

}
