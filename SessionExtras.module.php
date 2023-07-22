<?php namespace ProcessWire;

class SessionExtras extends WireData implements Module, ConfigurableModule {

	/**
	 * Ready
	 */
	public function ready() {
		if($this->track_ip || $this->track_ua) {
			$this->addHookAfter('ProcessWire::finished', $this, 'afterFinished');
		}
	}

	/**
	 * After ProcessWire::finished
	 *
	 * @param HookEvent $event
	 */
	protected function afterFinished(HookEvent $event) {
		$session = $this->wire()->session;
		if($this->track_ip) {
			$session->set('_user', 'ip', $session->getIP());
		}
		if($this->track_ua) {
			$ua = '';
			if(isset($_SERVER['HTTP_USER_AGENT'])) {
				$ua = substr(strip_tags($_SERVER['HTTP_USER_AGENT']), 0, 255);
			}
			$session->set('_user', 'ua', $ua);
		}
	}

	/**
	 * Config inputfields
	 *
	 * @param InputfieldWrapper $inputfields
	 */
	public function getModuleConfigInputfields($inputfields) {
		$modules = $this->wire()->modules;
		$description = $this->_('Checking this box will enable the data to be displayed in your admin sessions list.');

		/** @var InputfieldCheckbox $f */
		$f = $modules->get('InputfieldCheckbox');
		$f_name = 'track_ip';
		$f->name = $f_name;
		$f->label = $this->_('Track IP addresses in session data?');
		$f->description = $description;
		$f->checked = $this->$f_name === 1 ? 'checked' : '';
		$inputfields->add($f);

		$f = $modules->get('InputfieldCheckbox');
		$f_name = 'track_ua';
		$f->name = $f_name;
		$f->label = $this->_('Track user agent in session data?');
		$f->description = $description;
		$f->notes = $this->_('The user agent typically contains information about the browser being used.');
		$f->checked = $this->$f_name === 1 ? 'checked' : '';
		$inputfields->add($f);
	}

}
