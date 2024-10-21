<?php namespace ProcessWire;

class ProcessSessionInfo extends Process {

	/**
	 * Execute
	 *
	 * Much of this is the same as the core ProcessSessionDB::execute()
	 */
	public function ___execute() {
		$modules = $this->wire()->modules;
		$config = $this->wire()->config;
		$session = $this->wire()->session;
		$input = $this->wire()->input;
		$pages = $this->wire()->pages;
		$files = $this->wire()->files;

		require_once $this->wire()->config->paths->$this . 'Session.php';

		// Load Vex
		$this->wire()->modules->get('JqueryUI')->use('vex');

		// JS config
		$data = [
			'labels' => [
				'confirm_logout' => $this->_('Are you sure you want to force the logout of user'),
			],
		];
		$config->js($this->className, $data);

		// Extras
		$track_ip = false;
		$track_ua = false;
		if($modules->isInstalled('SessionExtras')) {
			$se = $modules->get('SessionExtras');
			$track_ip = (bool) $se->get('track_ip');
			$track_ua = (bool) $se->get('track_ua');
		}

		// Warning when SessionHandlerDB is installed
		if($modules->isInstalled('SessionHandlerDB')) {
			$this->wire()->warning($this->_('You have SessionHandlerDB installed, but ProcessSessionInfo is for sites that are using file-based sessions.'));
		}

		$mins = (int) $input->post('mins');
		if(!$mins) $mins = (int) $session->get('ProcessSessionInfo_mins');
		if(!$mins) $mins = 5;
		$session->set('ProcessSessionInfo_mins', $mins);

		/** @var InputfieldForm $form */
		$form = $modules->get('InputfieldForm');

		/** @var InputfieldInteger $field */
		$field = $modules->get('InputfieldInteger');
		$field->attr('name', 'mins');
		$field->attr('value', $mins);
		$field->label = sprintf($this->_n('Sessions active in last minute', 'Sessions active in last %d minutes', $mins), $mins);
		$field->description = $this->_('Number of minutes');
		$field->inputType = 'number';
		$field->collapsed = Inputfield::collapsedYes;
		$form->add($field);

		$limit = 500;
		$start = strtotime("$mins minutes ago");
		$show_page = $config->sessionHistory > 0;
		
		$path = rtrim(session_save_path(), '/') . '/';
		$session_files = $files->find($path, ['returnRelative' => true]);
		$results = [];
		$user_ids = [];
		$total = 0;
		foreach($session_files as $file) {
			if(substr($file, 0, 5) !== 'sess_') continue;
			$filepath = $path . $file;
			$modified = filemtime($filepath);
			if($modified < $start) continue;
			++$total;
			if($total > $limit) continue;
			$contents = @$files->fileGetContents($filepath);
			if(!$contents) {
				--$total;
				continue;
			}
			$info = \Session::unserialize($contents);

			$user_id = $info['Session']['_user']['id'] ?? $config->guestUserPageID;
			if(!$user_id) continue;
			$user_ids[] = $user_id;
			$result = [
				'time' => wireRelativeTimeStr($modified),
				'user' => $user_id,
			];
			if($show_page) {
				$history = $info['Session']['_user']['history'] ?? [];
				$history = reset($history);
				$url = $history['url'] ?? '';
				if($url) $url = parse_url($url, PHP_URL_PATH);
				$result['page'] = $url;
			}
			if($track_ip) $result['ip'] = $info['Session']['_user']['ip'] ?? '';
			if($track_ua) $result['ua'] = $info['Session']['_user']['ua'] ?? '';
			$results[$modified] = $result;
		}
		krsort($results);

		/** @var MarkupAdminDataTable $table */
		$table = $modules->get('MarkupAdminDataTable');
		if($track_ua) $table->addClass('track-ua');
		$table->sortable = false;
		$table->setEncodeEntities(false);
		$header = [
			$this->_('Time'),
			$this->_('User'),
		];
		if($show_page) $header[] = $this->_('Page');
		if($track_ip) $header[] = $this->_('IP Addr');
		if($track_ua) $header[] = $this->_('User Agent');
		$header[] = $this->_('Force logout');
		$table->headerRow($header);

		$user_ids_str = implode('|', $user_ids);
		$user_names = $pages->findRaw("id=$user_ids_str", ['name']);

		if($results) {
			foreach($results as $result) {
				$user_name = $user_names[$result['user']]['name'] ?? '';
				if(!$user_name) continue;
				$row = [
					$result['time'],
					"<a href='{$config->urls->admin}access/users/edit/?id={$result['user']}'>$user_name</a>"
				];
				if($show_page) $row[] = [$result['page'], 'psi-page'];
				if($track_ip) $row[] = $result['ip'];
				if($track_ua) $row[] = [$result['ua'], 'psi-ua'];
				if($result['user'] === $config->guestUserPageID) {
					$force_logout = '';
				} else {
					$force_logout = "<a class='force-logout' href='{$this->wire()->page->url}force-logout/?user={$result['user']}&name=$user_name'><i class='fa fa-sign-out'></i></a>";
				}
				$row[] = $force_logout;
				$table->row($row);
			}
			$table_out = $table->render();
		} else {
			$table_out = '<p class="description">' . $this->_('No active sessions') . '</p>';
		}

		$out =
			'<h2>' .
			'<i id="SessionListIcon" class="fa fa-2x fa-fw fa-tachometer ui-priority-secondary"></i> ' .
			sprintf($this->_n('%d active session', '%d active sessions', $total), $total) .
			'</h2>' .
			$table_out;

		if($config->ajax) return $out;

		/** @var InputfieldMarkup $markup */
		$markup = $modules->get('InputfieldMarkup');
		$markup->value = "<div id='SessionList'>$out</div>";
		$form->add($markup);

		/** @var InputfieldSubmit $submit */
		$submit = $modules->get('InputfieldSubmit');
		$submit->attr('value', $this->_('Refresh'));
		$submit->icon = 'refresh';
		$submit->attr('id+name', 'submit_session');
		$submit->showInHeader();
		$form->add($submit);

		return $form->render();
	}

	/**
	 * Force the logout of a user
	 */
	public function ___executeForceLogout() {
		$files = $this->wire()->files;
		$session = $this->wire()->session;
		$redirect = $this->wire()->page->url;

		require_once $this->wire()->config->paths->$this . 'Session.php';

		// There must be a user ID and it must not be the guest user ID
		$user_id = (int) $this->wire()->input->get('user');
		$name = $this->wire()->input->get->text('name');
		if(!$user_id || $user_id === $this->wire()->config->guestUserPageID) {
			$session->location($redirect);
		}

		// Loop over session files until we find the one for the user, then delete it
		$path = rtrim(session_save_path(), '/') . '/';
		$session_files = $files->find($path, ['returnRelative' => true]);
		foreach($session_files as $file) {
			if(substr($file, 0, 5) !== 'sess_') continue;
			$filepath = $path . $file;
			$contents = @$files->fileGetContents($filepath);
			if(!$contents) continue;
			$info = \Session::unserialize($contents);
			if(!isset($info['Session']['_user']['id']) || $info['Session']['_user']['id'] !== $user_id) continue;
			$files->unlink($filepath);
			$session->message(sprintf($this->_('User "%s" has been logged out.'), $name));
			$session->location($redirect);
			break;
		}
	}

}
