<?php

/**
 * @file classes/press/NewReleaseDAO.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NewReleaseDAO
 * @ingroup press
 * @see NewRelease
 *
 * @brief Operations for setting new release status on various items.
 */

class NewReleaseDAO extends DAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Get monograph IDs by association.
	 * @param $assocType int ASSOC_TYPE_...
	 * @param $assocId int
	 * @return array monographId
	 */
	function getMonographIdsByAssoc($assocType, $assocId) {
		$returner = array();
		$result = $this->retrieve(
			'SELECT submission_id FROM new_releases WHERE assoc_type = ? AND assoc_id = ?',
			array((int) $assocType, (int) $assocId)
		);

		while (!$result->EOF) {
			list($monographId) = $result->fields;
			$returner[$monographId] = true;
			$result->MoveNext();
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Get monographs by association.
	 * @param $assocType int ASSOC_TYPE_...
	 * @param $assocId int
	 * @return array Monograph
	 */
	function getMonographsByAssoc($assocType, $assocId) {
		// import STATUS_PUBLISHED constant
		import('classes.submission.Submission');
		$result = $this->retrieve(
			'SELECT	n.submission_id
			FROM	new_releases n,
				submissions s,
				publications p
			WHERE	n.submission_id = s.submission_id
				AND p.publication_id = s.current_publication_id
				AND n.assoc_type = ? AND n.assoc_id = ?
				AND s.status = ?
			ORDER BY p.date_published DESC',
			array((int) $assocType, (int) $assocId, STATUS_PUBLISHED)
		);

		$returner = array();
		while (!$result->EOF) {
			list($monographId) = $result->fields;
			$returner[] = Services::get('submission')->get($monographId);
			$result->MoveNext();
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Insert a new NewRelease.
	 * @param $monographId int
	 * @param $assocType int ASSOC_TYPE_...
	 * @param $assocId int
	 */
	function insertNewRelease($monographId, $assocType, $assocId) {
		$this->update(
			'INSERT INTO new_releases
				(submission_id, assoc_type, assoc_id)
				VALUES
				(?, ?, ?)',
			array(
				(int) $monographId,
				(int) $assocType,
				(int) $assocId
			)
		);
	}

	/**
	 * Delete a new release by ID.
	 * @param $monographId int
	 * @param $pressId int optional
	 */
	function deleteByMonographId($monographId) {
		$this->update(
			'DELETE FROM new_releases WHERE submission_id = ?',
			(int) $monographId
		);
	}

	/**
	 * Delete a new release by association.
	 * @param $assocType int ASSOC_TYPE_...
	 * @param $assocId int
	 */
	function deleteByAssoc($assocType, $assocId) {
		$this->update(
			'DELETE FROM new_releases WHERE assoc_type = ? AND assoc_id = ?',
			array((int) $assocType, (int) $assocId)
		);
	}

	/**
	 * Delete a new release.
	 * @param $monographId int
	 * @param $assocType int ASSOC_TYPE_...
	 * @param $assocId int
	 */
	function deleteNewRelease($monographId, $assocType, $assocId) {
		$this->update(
			'DELETE FROM new_releases
			WHERE	submission_id = ? AND
				assoc_type = ? AND
				assoc_id = ?',
			array(
				(int) $monographId,
				(int) $assocType,
				(int) $assocId
			)
		);
	}

	/**
	 * Check if the passed monograph id is marked as new release
	 * on the passed associated object.
	 * @param $monographId int The monograph id to check the new release state.
	 * @param $assocType int The associated object type that the monograph
	 * is checked for a new release mark.
	 * @param $assocId int The associated object id that the monograph is
	 * checked for a new release mark.
	 * @return boolean Whether or not the monograph is marked as a new release.
	 */
	function isNewRelease($monographId, $assocType, $assocId) {
		$result = $this->retrieve(
			'SELECT submission_id FROM new_releases WHERE submission_id = ? AND assoc_type = ? AND assoc_id = ?',
			array((int) $monographId, (int) $assocType, (int) $assocId)
		);
		if ($result->RecordCount() > 0) {
			return true;
		}

		return false;
	}

	/**
	 * Return the monograph's new release settings in all assoc types
	 *
	 * @param $monographId int The monograph ID to get the new release state
	 * @return array
	 */
	function getNewReleaseAll($monographId) {
		$result = $this->retrieve(
			'SELECT assoc_type, assoc_id FROM new_releases WHERE submission_id = ?',
			array((int) $monographId)
		);

		$newRelease = array();
		while (!$result->EOF) {
			$newRelease[] = array(
				'assoc_type' => (int) $result->fields['assoc_type'],
				'assoc_id' => (int) $result->fields['assoc_id'],
			);
			$result->MoveNext();
		}

		return $newRelease;
	}
}


