<?php

/**
* ownCloud - News
*
* @author Alessandro Cosentino
* @author Bernhard Posselt
* @copyright 2012 Alessandro Cosentino cosenal@gmail.com
* @copyright 2012 Bernhard Posselt nukeawhale@gmail.com
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

namespace OCA\News\Db;

use \OCA\AppFramework\Core\API;
use \OCA\AppFramework\Db\Mapper;
use \OCA\AppFramework\Db\Entity;


class FolderMapper extends Mapper implements IMapper {

	public function __construct(API $api) {
		parent::__construct($api, 'news_folders');
	}

	public function find($id, $userId){
		$sql = 'SELECT * FROM `*PREFIX*news_folders` ' .
			'WHERE `id` = ? ' .
			'AND `user_id` = ?';

		$row = $this->findOneQuery($sql, array($id, $userId));
		$folder = new Folder();
		$folder->fromRow($row);

		return $folder;
	}


	private function findAllRows($sql, $params=array()){
		$result = $this->execute($sql, $params);
		
		$folders = array();
		while($row = $result->fetchRow()){
			$folder = new Folder();
			$folder->fromRow($row);
			array_push($folders, $folder);
		}

		return $folders;
	}


	public function findAllFromUser($userId){
		$sql = 'SELECT * FROM `*PREFIX*news_folders` ' .
			'WHERE `user_id` = ? ' .
			'AND `deleted_at` = 0';
		$params = array($userId);

		return $this->findAllRows($sql, $params);
	}


	public function findByName($folderName, $userId){
		$sql = 'SELECT * FROM `*PREFIX*news_folders` ' .
			'WHERE `name` = ? ' .
			'AND `user_id` = ?';
		$params = array($folderName, $userId);

		return $this->findAllRows($sql, $params);
	}


	public function delete(Entity $entity){
		parent::delete($entity);

		// someone please slap me for doing this manually :P
		// we needz CASCADE + FKs please
		$sql = 'DELETE FROM `*PREFIX*news_feeds` WHERE `folder_id` = ?; '.
			'DELETE `items` FROM `*PREFIX*news_items` `items` '.
			'LEFT JOIN `*PREFIX*news_feeds` `feeds` ON '. 
			'`items`.`feed_id` = `feed`.`id` WHERE `feeds`.`id` IS NULL;';
		$params = array($entity->getId());
		$this->execute($sql, $params);
	}


	public function getToDelete($deleteOlderThan, $userId=null) {
		$sql = 'SELECT * FROM `*PREFIX*news_folders` ' .
			'WHERE `deleted_at` > 0 ' .
			'AND `deleted_at` < ?';
		$params = array($deleteOlderThan);

		// we need to sometimes only delete feeds of a user
		if($userId !== null) {
			$sql .= ' AND `user_id` = ?';
			array_push($params, $userId);
		}
		
		return $this->findAllRows($sql, $params);
	}



}