<?php
/**
 * Rollback all edits by a given user or IP provided they're the most
 * recent edit (just like real rollback)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Maintenance
 */

use MediaWiki\Title\Title;
use MediaWiki\User\User;

// @codeCoverageIgnoreStart
require_once __DIR__ . '/Maintenance.php';
// @codeCoverageIgnoreEnd

/**
 * Maintenance script to rollback all edits by a given user or IP provided
 * they're the most recent edit.
 *
 * @ingroup Maintenance
 */
class RollbackEdits extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription(
			"Rollback all edits by a given user or IP provided they're the most recent edit" );
		$this->addOption(
			'titles',
			'A list of titles, none means all titles where the given user is the most recent',
			false,
			true
		);
		$this->addOption( 'user', 'A user or IP to rollback all edits for', true, true );
		$this->addOption( 'summary', 'Edit summary to use', false, true );
		$this->addOption( 'bot', 'Mark the edits as bot' );
		$this->setBatchSize( 10 );
	}

	public function execute() {
		$user = $this->getOption( 'user' );
		$services = $this->getServiceContainer();
		$userNameUtils = $services->getUserNameUtils();
		$user = $userNameUtils->isIP( $user ) ? $user : $userNameUtils->getCanonical( $user );
		if ( !$user ) {
			$this->fatalError( 'Invalid username' );
		}

		$bot = $this->hasOption( 'bot' );
		$summary = $this->getOption( 'summary', $this->mSelf . ' mass rollback' );
		$titles = [];
		if ( $this->hasOption( 'titles' ) ) {
			foreach ( explode( '|', $this->getOption( 'titles' ) ) as $text ) {
				$title = Title::newFromText( $text );
				if ( !$title ) {
					$this->error( 'Invalid title, ' . $text );
				} else {
					$titles[] = $title;
				}
			}
		} else {
			$titles = $this->getRollbackTitles( $user );
		}

		if ( !$titles ) {
			$this->output( 'No suitable titles to be rolled back.' );

			return;
		}

		$doer = User::newSystemUser( User::MAINTENANCE_SCRIPT_USER, [ 'steal' => true ] );
		$byUser = $services->getUserIdentityLookup()->getUserIdentityByName( $user );

		if ( !$byUser ) {
			$this->fatalError( 'Unknown user.' );
		}

		$wikiPageFactory = $services->getWikiPageFactory();
		$rollbackPageFactory = $services->getRollbackPageFactory();

		/** @var iterable<Title[]> $titleBatches */
		$titleBatches = $this->newBatchIterator( $titles );

		foreach ( $titleBatches as $titleBatch ) {
			foreach ( $titleBatch as $title ) {
				$page = $wikiPageFactory->newFromTitle( $title );
				$this->output( 'Processing ' . $title->getPrefixedText() . '...' );

				$this->beginTransactionRound( __METHOD__ );
				$rollbackResult = $rollbackPageFactory
					->newRollbackPage( $page, $doer, $byUser )
					->markAsBot( $bot )
					->setSummary( $summary )
					->rollback();
				$this->commitTransactionRound( __METHOD__ );

				if ( $rollbackResult->isGood() ) {
					$this->output( "Done!\n" );
				} else {
					$this->output( "Failed!\n" );
				}
			}
		}
	}

	/**
	 * Get all pages that should be rolled back for a given user
	 * @param string $user A name to check against
	 * @return Title[]
	 */
	private function getRollbackTitles( $user ) {
		$dbr = $this->getReplicaDB();
		$titles = [];

		$results = $dbr->newSelectQueryBuilder()
			->select( [ 'page_namespace', 'page_title' ] )
			->from( 'page' )
			->join( 'revision', null, 'page_latest = rev_id' )
			->join( 'actor', null, 'rev_actor = actor_id' )
			->where( [ 'actor_name' => $user ] )
			->caller( __METHOD__ )->fetchResultSet();
		foreach ( $results as $row ) {
			$titles[] = Title::makeTitle( $row->page_namespace, $row->page_title );
		}

		return $titles;
	}
}

// @codeCoverageIgnoreStart
$maintClass = RollbackEdits::class;
require_once RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd
