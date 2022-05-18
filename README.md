# pcomposer

Based on the ideology of [pnpm](https://pnpm.io) to reduce and reuse shared packages.

## Install

`composer global require bkief29/pcomposer`

## Setup

Modify your ~/.composer/config.json to include the following:

> Replace `%MY_USER%` with your user, or specify a custom path for `vendor-dir`

```json
{
    "config": {
        "allow-plugins": {
            "bkief29/pcomposer": true
        },
        "extra" : {
            "shared-package": {
                "vendor-dir": "/Users/%MY_USER%/.composer/pcomposer",
                "symlink-dir": "vendor",
                "symlink-enabled": true,
                "package-list": [
                    "*"
                ]
            }
        
        }
    }
}

```

## Options

### Exclude packages from being symlinked

In your project's composer.json:

```json
"config": {
    "extra" : {
        "pcomposer": {
            "exclude": [
                "spatie/data-transfer-object"
            ]
        }
    }
},
```

## Extras

### Migrate to pcomposer for all of your projects

> Usage: `pcomposer ~/projects`

```shell
function pcomposer() {
  find $1 -type d \( -name "vendor" \) -maxdepth 2 -print0 | while read -d $'\0' file
  do
     echo "$file"
     cd "$file/../" && composer install
  done
}
```
