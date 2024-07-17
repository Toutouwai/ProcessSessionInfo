<?php namespace ProcessWire;

class ProcessSessionInfo extends Process {

	/**
	 * Execute
	 *
	 * Much of this is the same as the core ProcessSessionDB::execute()
	 */
	public function ___execute() {
		require_once $this->wire()->config->paths->$this . 'Session.php';

		$modules = $this->wire()->modules;
		$config = $this->wire()->config;
		$session = $this->wire()->session;
		$input = $this->wire()->input;
		$pages = $this->wire()->pages;
		$files = $this->wire()->files;
		
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
		$table->headerRow($header);

		$user_ids_str = implode('|', $user_ids);
		$user_names = $pages->findRaw("id=$user_ids_str", ['name']);

		if($results) {
			foreach($results as $result) {
				$row = [
					$result['time'],
					"<a href='{$config->urls->admin}access/users/edit/?id={$result['user']}'>{$user_names[$result['user']]['name']}</a>"
				];
				if($show_page) $row[] = [$result['page'], 'psi-page'];
				if($track_ip) $row[] = $result['ip'];
				if($track_ua) $row[] = [$result['ua'], 'psi-ua'];
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

}
