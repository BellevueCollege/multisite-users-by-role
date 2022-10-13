<?php
class MBUR_Commands {

	/**
	 * Return a comma separated list of IDs of users with the specified role(s)
	 */
	private function rtn_id_csv( $use_stored = true, $include_super = true, $roles = array( 'administrator' ) ) {
		if ( $use_stored ) {
			$roles = get_site_option( 'multisite_user_list_selected_role' );
		}
		$user_list = new MUBR_User_List();
		$user_list->setRoles( $roles );
		$user_list->loadUsers();
		$users_csv = $user_list->get_ids_csv();
		if ( ! $include_super ) {
			return $users_csv; // return comma separated list of IDs
		}
		// If we want to include super admins, we need to get their IDs and add them to the list
		$super_admins = $this->rtn_super_admin_id();
		// Combine Unique IDs
		$combined_csv = implode( "," , array_unique( array_merge( explode( ",", $users_csv ) , $super_admins  ) ) );
		return $combined_csv; // return comma separated list of IDs

	}

	/**
	 * Return a comma separated list of IDs of Super Admins
	 */
	private function rtn_super_admin_id() {
		$super_admins = get_super_admins();
		$super_admin_ids = array();
		foreach ( $super_admins as $super_admin ) {
			@ $id = get_user_by( 'login', $super_admin )->ID;
			if ( $id ) {
				$super_admin_ids[] = $id;
			}
		}
		return $super_admin_ids;
	}

	public function super_id_list() {
		WP_CLI::success( $this->rtn_super_admin_id() );
	}

	/**
	 * Returns IDS of users with a certain role.
	 *
	 * ## OPTIONS
	 *
	 * [--use-stored-roles]
	 * : Use roles from the Options table (can be set via the plugin interface).
	 * [--s]
	 * : Shortcut for --use-stored-roles
	 * [--<roles>=<roles>]
	 * : Comma separated list of roles to use.
	 * [--<r>=<roles>]
	 * : Shortcut for --roles
	 *
	 * ## EXAMPLES
	 *
	 *     wp mubr id_list --roles=administrator,editor
	 *
	 * @when after_wp_load
	 */
	public function id_list( $args, $assoc_args ) {
		if ( isset( $assoc_args['use-stored-roles'] ) || isset( $assoc_args['s'] ) ) {
			WP_CLI::log( 'Using stored roles.' );
			WP_CLI::success( $this->rtn_id_csv() );
		} elseif ( isset( $assoc_args['roles'] ) || isset( $assoc_args['r'] ) ) {
			$roles = $assoc_args['roles'] ?? $assoc_args['r'];
			$roles = explode( ',', $roles );
			WP_CLI::success( $this->rtn_id_csv( false, true, $roles ) );
		} else {
			WP_CLI::log( 'No roles specified. Defaulting to administrator role. Use --roles specify roles, or --use-stored-roles to use currently saved role set.' );
			WP_CLI::success( $this->rtn_id_csv( false ) );
		}
	}

	/**
	 * Return a comma separated list of all users NOT in the input array
	 */
	private function rtn_inverse( $csv, $network = true, $url = false ) {
		$options = array(
			'return'     => true,   // Return 'STDOUT'; use 'all' for full object.
			'parse'      => 'json', // Parse captured STDOUT to JSON array.
			'launch'     => false,  // Reuse the current process.
			'exit_error' => true,   // Halt script execution on error.
		);
		if ( $network ) {
			$output = WP_CLI::runcommand( 'user list --network --format=json --field=ID --exclude=' . $csv, $options );
		} elseif ( ! $network && $url ) {
			$output = WP_CLI::runcommand( 'user list --url=' . $url . ' --format=json --field=ID --exclude=' . $csv, $options );
		} else {
			$output = WP_CLI::runcommand( 'user list --format=json --field=ID --exclude=' . $csv, $options );
		}
		$output = implode( ',', $output );
		return $output;
	}

	/**
	 * Delete users from sites or the network in batches
	 */
	private function batch_delete( $ids, $url = false, $network = false, $reassign = false, $batch_size = 1000 ) {
		$batch_size = (int) $batch_size;
		$ids = explode( ',', $ids );
		$batches = array_chunk( $ids, $batch_size );

		$url = $url ? '--url=' . $url : '';
		$network = $network ? '--network --yes' : '';
		$reassign = $reassign ? '--reassign=' . $reassign : '';

		foreach ( $batches as $index => $batch ) {
			WP_CLI::log( 'Deleting ' . count( $batch ) . ' users in batch ' . ( $index + 1 ) . ' of ' . count( $batches ) . '...' );
			//WP_CLI::log( "I would run: user delete $network $url $reassign " . implode( ' ', $batch ) );
			WP_CLI::runcommand( "user delete --quiet $network $url $reassign " . implode( ' ', $batch ), array( 'launch' => true ) );
			WP_CLI::log( 'Deleted ' . count( $batch ) . ' users in batch ' . ( $index + 1 ) . ' of ' . count( $batches ) );
		}
	}

	/**
	 * Deletes users WITHOUT a certain role from the network
	 *
	 * ## EXAMPLES
	 *
	 *     wp mubr delete_users_from_subsites --roles=administrator,editor --network --reassign=1 --batch-size=100 --DELETE
	 *
	 * @when after_wp_load
	 */
	public function delete_users_from_subsites( $args, $assoc_args ) {
		$options = array(
			'return'     => true,   // Return 'STDOUT'; use 'all' for full object.
			'parse'      => 'csv', // Parse captured STDOUT to CSV
			'launch'     => false,  // Reuse the current process.
			'exit_error' => false,   // Halt script execution on error.
		);

		// Check if --network is set (currently required- no behavior if not set)
		if ( isset( $assoc_args['network'] ) ) {
			if ( isset( $assoc_args['use-stored-roles'] ) || isset( $assoc_args['s'] ) ) {
				WP_CLI::log( 'Getting users using stored roles.' );
				$users_to_keep = $this->rtn_id_csv();
			} elseif ( isset( $assoc_args['roles'] ) || isset( $assoc_args['r'] ) ) {
				$roles = $assoc_args['roles'] ?? $assoc_args['r'];
				$users_to_keep = $this->rtn_id_csv( false, true, explode( ',', $roles ) );
			} else {
				$users_to_keep = $this->rtn_id_csv( false, true );
			}
			$users_to_delete = $this->rtn_inverse( $users_to_keep );

		} else {
			WP_CLI::error( 'This command only works on a multisite network. Please use the --network flag.' );
		}

		WP_CLI::log( "Keeping User IDs $users_to_keep; Deleting IDs $users_to_delete" );
		//WP_CLI::log( "Keeping User IDs $users_to_keep" );


		// Get all the sites in the network.
		$sites = get_sites();

		// Delete users from each site.
		foreach ( $sites as $site ) {
			$url = $site->domain . $site->path;
			//WP_CLI::log( "I would run: user delete --url=$url $reassign $users_to_delete" );

			$site_batch_id;
			switch_to_blog( $site->blog_id );
				$site_batch_id = get_option( 'mubr_delete_users_batch_id' ,'blank!');
			restore_current_blog();

			if ( ! isset( $assoc_args['batch_id'] ) || $site_batch_id !== $assoc_args['batch_id'] ) {
				WP_CLI::log( "No matching batch ID for $url. ID is '$site_batch_id'. Deleting Users Now!" );

				// Delete Users in Batches
				$this->batch_delete(
					$users_to_delete,
					$url,
					false, // don't network delete
					$assoc_args['reassign'],
					$assoc_args['batch-size'] ?? 1000 // users in a batch
				);

				// Add Batch ID to Site Meta to allow this site to be skipped next time the command is run
				if ( isset( $assoc_args['batch_id'] ) ) {
					//update_site_meta( $site->site_id, 'mubr_delete_users_batch_id', $assoc_args['batch_id'] );
					WP_CLI::runcommand( "option update mubr_delete_users_batch_id " . $assoc_args['batch_id'] . " --url=$url" );
				}
			} else {
				WP_CLI::log( "Skipping $url. A matching Batch ID ($site_batch_id) was found." );
				continue;
			}
			
		}

		// Delete users from the network if --DELETE is set.
		if ( $assoc_args['DELETE'] ) {
			WP_CLI::log( 'Deleting users from the network...' );
			$this->batch_delete(
				$users_to_delete,
				$url,
				true, //network delete!
				false,
				$assoc_args['batch-size'] ?? 1000 // users in a batch
			);
		} else {
			WP_CLI::log( 'Not network deleting users. Use --DELETE to delete users.' );
		}
		WP_CLI::success( 'Done!' );
	}


	public function batch_check( $args, $assoc_args ) {
			
		// Get all the sites in the network.
		$sites = get_sites();

		// Delete users from each site.
		foreach ( $sites as $site ) {
			$url = $site->domain . $site->path;
			$site_id = $site->site_id;
			//WP_CLI::log( "I would run: user delete --url=$url $reassign $users_to_delete" );

			$site_batch_id = get_site_meta( $site->site_id, 'mubr_delete_users_batch_id', true );

			if ( ! isset( $assoc_args['batch_id'] ) || $site_batch_id !== $assoc_args['batch_id'] ) {
				WP_CLI::log( "No matching batch ID for $url ($site_id). Batch ID is '$site_batch_id'. This site would be processed" );

			} else {
				WP_CLI::log( "Skip: $url ($site_id). A matching Batch ID ($site_batch_id) was found." );
			}
		}
	}
}