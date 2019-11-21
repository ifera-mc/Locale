# Locale

A simple virion for PocketMine-MP plugins to send translated messages to players using their locale.

## Important Note(s)

- Make sure the language file you are making is supported. (yaml, json, txt, ini, etc).
- `identifier` key should exist in all the language files you make. It should be set as `en_US` or `en_GB` or whatever. The list of required ones is in the Locale::ALLOWED_IDENTIFIERS.
- At least one of the language files should have a `fallbackIdentifier` that would be used in case players locale doesn't exist.
- Language present in `resources\lang` dir will be saved automatically to `plugin_data` path if `$saveFilesToPath` is true.
- The virion will auto load all the valid language files in the `lang` directory and save or use them.
- Added feature to use `&` in translations for color codes.

## Caution

Bundle this virion into the plugin before using in production.

## Initialize

Initialize the virion by doing<br />

```php
Locale::init(PluginBase $plugin, string $fallbackIdentifier = "en_US", bool $saveFilesToPath = true): void;
```

- `$plugin` is pretty self explanatory.
- `$fallbackIdentifier` is the identifier that's used in case the players required translation is missing.
- `$saveFilesToPath` either save files to plugin_data or load the from the source i.e. from plugins folder.

## Get Translated Message

```php
Locale::getTranslation(string $langIdentifier, string $messageIdentifier, array $args = []): string;
```

- `$langIdentifier` will either be `en_US` or one of the allowed identifiers.
- `$messageIdentifier` the message key to translate.
- `$args` array of placeholders in the string. Example `["{player}" => "player name", "ip" => "players ip"]`

## Send Translated Message

Automatically send the translated message based on the players locale. If the translation file exists then it is used else the fallback translation is used.

```php
Locale::sendTranslatedMessage(CommandSender $sender, string $messageIdentifier, array $args = []): void;
```

- `$sender` the player or console user.
- The rest are same as above.

### Additional Note(s)

- Hopefully I mentioned every required detail.
- For any feature additions or bug reports please open an issue.
- PRs are always welcomed.
