# Session Info

Lists information about active sessions in a similar way to SessionHandlerDB, but for file-based sessions.

Only install the module if you are not already using SessionHandlerDB.

## Installation

1\. If you want to be able to see the pages that are being viewed by active sessions then set...

```php
$config->sessionHistory = 1;
```
...in /site/config.php

If you have already set `$config->sessionHistory` to a higher number then you can leave it unchanged: `1` is the minimum needed for use in the Session Info module.

2\. [Install](http://modules.processwire.com/install-uninstall/) the Session Info module. A helper module named "Session Extras" will be automatically installed also.

3\. If you want to be able to see the IP address and/or user agent for active sessions then visit the module config page for Session Extras and tick the relevant checkboxes.

![session-info-2](https://github.com/Toutouwai/ProcessSessionInfo/assets/1538852/494ca887-75ab-4ba7-86e5-f2f610fba088)

4\. You can now view information about active sessions at Access > Sessions.

## Screenshots

With `$config->sessionHistory` set to 1 or higher:

![session-info-1](https://github.com/Toutouwai/ProcessSessionInfo/assets/1538852/1e76706d-5cee-4b16-8c52-4df7e0b1b5f0)

Additional information is listed when IP and user agent tracking are enabled in Session Extras:

![session-info-3](https://github.com/Toutouwai/ProcessSessionInfo/assets/1538852/ec0f8c87-33b8-4c93-aeb3-caab586ae125)
