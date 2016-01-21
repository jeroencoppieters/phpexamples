<?php

	/**
	 * Includes
	 * ----------------------------------------------------------------
	 */

		// config & functions
		require_once 'includes/config.php';
		require_once 'includes/functions.php';
		require_once __DIR__ . '/includes/Twig/Autoloader.php';
		Twig_Autoloader::register();
		$loader = new Twig_Loader_Filesystem(__DIR__ . '/templates');
		$twig = new Twig_Environment($loader);


	/**
	 * Session Control: Only allow logged in users to this site
	 * ----------------------------------------------------------------
	 */

		// start session
		session_start();

		// user is not logged in, redirect to login
		if (!isset($_SESSION['user'])) {
			header('location: login.php');
			exit();
		}


	/**
	 * Database Connection
	 * ----------------------------------------------------------------
	 */

		$db = getDatabase();


	/**
	 * Initial Values
	 * ----------------------------------------------------------------
	 */


		$formErrors = array(); // The encountered form errors
		$id = isset($_GET['id']) ? (int) $_GET['id'] : 0; // The passed in id of the todo

	/**
	 * Handle action 'edit' (user pressed edit button)
	 * ----------------------------------------------------------------
	 */

		if (isset($_POST['moduleAction']) && ($_POST['moduleAction'] == 'delete')) {

			// get the id
			$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

			// check if item exists (use the id from the $_POST array!)
			$stmt = $db->prepare('SELECT COUNT(*) FROM todolist WHERE id = ? AND user_id = ?');
			$stmt->execute(array($id, $_SESSION['user']['id']));
			$numItems = $stmt->fetchColumn();

			if ($numItems != 1) {
				header('location: browse.php');
				exit();
			}

			// form is correct: update values into database
			if (sizeof($formErrors) == 0) {

				// build and execute statement
				$stmt = $db->prepare('DELETE FROM todolist WHERE id = ?');
				$stmt->execute(array($id));

				// the query succeeded, redirect to this very same page
				if ($stmt->rowCount() != 0) {
					header('location: browse.php');
					exit();
				}

				// the query failed
				else {
					 $formErrors[] = 'Error while deleting the item. Please retry.';
				}

			}

		}

	/**
	 * No action to handle: show delete page
	 * ----------------------------------------------------------------
	 */

		// Check if the passed in id (in $_GET) exists as a todoitem
		$stmt = $db->prepare('SELECT * FROM todolist WHERE id = ? AND user_id = ?');
		$stmt->execute(array($id, $_SESSION['user']['id']));

		if ($stmt->rowCount() != 1) {
			header('location: browse.php');
			exit();
		}

		// Get the item from the database
		$item = $stmt->fetch(PDO::FETCH_ASSOC);

	/**
	 * Load and render template
	 * ----------------------------------------------------------------
	 */

		$tpl = $twig->loadTemplate('delete.twig');
		echo $tpl->render(array(
			'action' => $_SERVER['PHP_SELF'] . '?id=' . htmlentities(urlencode($id)),
			'item' => $item,
			'formErrors' => $formErrors,
			'user' => $_SESSION['user']
		));


// EOF