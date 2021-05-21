Update external modules using `yarn`:

```bash
$ yarn
```

Yarn installation
=================

- Install yarn >= 1.13.0. If you are using Debian **do not install the cmdtest package**. Remove it instead, and proceed like this:
```bash
# apt install nodejs npm
# curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
# echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
# apt update
# apt install yarn
```
See https://classic.yarnpkg.com/en/docs/install for help installing yarn.
