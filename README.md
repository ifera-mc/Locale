# Locale

A simple yet powerful virion for PocketMine-MP plugins to send translated messages to players using their preferred languages.

## Important Note(s)

- Make sure the language file you are making is supported. (yaml, json, txt, etc). .ini isn't supported.
- `identifier` key should exist in all the language files you make. It should be set as `en_US` or `en_GB` or whatever. The list of required ones is in the Locale::ALLOWED_IDENTIFIERS.
- At least one of the language files should have a fallbackIdentifier that would be used in case the player language doesn't exist.
- Language files should exist in `plugin_data/your plugin name/lang/` directory.
- The virion will auto load all the valid language files in the lang directory.

## Caution

Bundle this virion into the plugin before using in production.

## Initialize

Initialize the virion by doing<br />

```php
    Locale::init(Plugin $plugin, string $fallbackIdentifier = "en_US");
```

- `$plugin` is pretty self explanatory.
- `$fallbackIdentifier` is the identifier that's used in case the players required translation is missing.

## Get Translated Message

```php
    Locale::getTranslation(string $langIdentifier, string $messageIdentifier, array $toFind = [], array $toReplace = []): string;
```

- `$langIdentifier` will either be `en_US` or one of the allowed identifiers.
- `$messageIdentifier` the message key to translate.
- `$toFind` array of placeholders in the string.
- `$toReplace` array of placeholders to replace them with.

## Send Translated Message

Automatically send the translated message based on the players locale. If the translation file exists then it is used else the fallback translation is used.

```php
    Locale::sendTranslatedMessage(CommandSender $sender, string $messageIdentifier, array $toFind = [], array $toReplace = []): void;
```

- `$sender` the player or console user.
- The rest are same as above.

### Additional Note(s)

- Hopefully I mentioned every required detail.
- For any feature additions or bug reports to open an issue.
- PRs are always welcomed.