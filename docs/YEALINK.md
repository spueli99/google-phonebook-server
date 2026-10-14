# Yealink setup

Phonebook Sync currently supports Yealink Remote Phone Book XML.

## 1. Create the device in Phonebook Sync

Open:

```text
Geräte → Gerät hinzufügen
```

Enter a clear name such as:

```text
T54W Wohnzimmer
```

Enable **Kontaktverzeichnis freigeben**.

If desired, enable **Sperrlisten-Verzeichnis** as a second Remote Phone Book.

After saving, Phonebook Sync creates a random device ID and a 256-bit token.

## 2. Contact URL

The URL has this form:

```text
https://phonebook.example.de/export.php?device=dev_0123456789ab&book=contacts&token=<DEVICE_TOKEN>
```

Treat the entire URL as a secret because the token is part of the query string.

## 3. Test the generated URL

Before configuring the phone, verify the generated URL from a trusted browser or command line. Keep the real token private.

Example with a placeholder token:

```bash
curl 'https://phonebook.example.de/export.php?device=dev_0123456789ab&book=contacts&token=<DEVICE_TOKEN>'
```

A successful response starts with Yealink XML similar to:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<YealinkIPPhoneDirectory>
```

A wrong token must return HTTP `403`. If no Google synchronization has completed yet, the server returns HTTP `503`.

Do not paste the real URL into public logs, issues or screenshots.

## 4. Configure the Yealink phone

In the Yealink web interface open the Remote Phone Book configuration. Menu names can vary by model/firmware, commonly under:

```text
Directory → Remote Phone Book
```

Configure for example:

```text
Display Name: Google Kontakte
Remote URL:   <generated contacts URL>
```

The T54W configuration tested with this project does not require separate username/password fields because authentication is performed with the per-device token in the URL.

## 5. Refresh interval

Choose a refresh interval appropriate for the actual use of the phone.

Examples:

```text
3600   = 1 hour
86400  = 24 hours
```

The Google synchronization interval and the Yealink refresh interval are independent.

Example:

```text
Google/server sync:  every 5 minutes
Yealink directory:   once per day
```

The server cache can therefore be current even if the phone downloads it only once daily.

## 6. Number labels

Phonebook Sync preserves Google phone-number labels and renders them in Yealink XML, for example:

```xml
<DirectoryEntry>
  <Name>Max Mustermann</Name>
  <Telephone label="Mobil">+491701234567</Telephone>
  <Telephone label="Büro">+492159123456</Telephone>
</DirectoryEntry>
```

This avoids generic labels such as `Nummer 1`, `Nummer 2` where the Yealink firmware supports Remote Phone Book labels.

## 7. Central blocklist directory

When enabled for a device, a second URL is generated:

```text
https://phonebook.example.de/export.php?device=dev_0123456789ab&book=blocklist&token=<DEVICE_TOKEN>
```

This can be configured as another Yealink Remote Phone Book, for example named:

```text
Sperrliste
```

Important: this directory is a centrally maintained list and **does not itself make the Yealink reject calls**. Actual call blocking remains a local Yealink blocklist function. Entries can be used as a convenient source for manual transfer where supported by the phone.

## 8. Device access status

Each successful directory request updates:

- last access time
- last requested directory (`contacts` or `blocklist`)
- access counter

This makes it easy to verify from the admin UI whether the phone is still fetching the directory.

## 9. Token rotation

If a device URL becomes exposed:

1. open **Geräte**
2. edit the device
3. enable **Geräte-Token neu erzeugen**
4. save
5. replace the Remote Phone Book URL in the phone

The old token immediately stops working.

## 10. Remove a device

Deleting a device removes its authorization. Its previous URL becomes invalid immediately.
