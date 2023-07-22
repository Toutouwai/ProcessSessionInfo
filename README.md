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

![session-info-2](https://github.com/Toutouwai/ProcessSessionInfo/assets/1538852/28cf60f4-f47b-4e0b-ad93-b5480fc7e03f)


4\. You can now view information about active sessions at Access > Sessions.

## Screenshots

With `$config->sessionHistory` set to 1 or higher:

![session-info-1](https://github.com/Toutouwai/ProcessSessionInfo/assets/1538852/c6223869-eeaf-4214-ae58-85838e50483d)


Additional information is listed when IP address and user agent tracking are enabled in Session Extras:

![session-info-3](https://github.com/Toutouwai/ProcessSessionInfo/assets/1538852/d7647e81-050a-4a9a-825f-6852cd3f20a4)
