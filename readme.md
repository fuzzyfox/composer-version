# composer-version
## Bump a package version

Use it in a single project:
```shell
composer require fuzzyfox/composer-version
```

Or use it everywhere:
```shell
composer global require fuzzyfox/composer-version
```

### Synopsis
```shell
composer version [<newversion> | major | minor | patch | premajor | preminor | prepatch | devmajor | devminor | devpatch | prerelease [--preid=<prerelease-id>] | from-git]

composer version # to print current package/composer version
```

### Description
Run this package in a directory to bump the version and write the new data back
to `composer.json`.

The `newversion` argument should be a valid [composer version string](https://getcomposer.org/doc/04-schema.md#version),
or, one of the following:

 * `major`  - Bump to the next major version.
 * `minor`  - Bump to the next minor version.
 * `patch`  - Bump to the next patch version.
 * `premajor [--preid=<prerelease-id>]` - Bump to the next major version with a prerelease suffix.
 * `preminor [--preid=<prerelease-id>]` - Bump to the next minor version with a prerelease suffix.
 * `prepatch [--preid=<prerelease-id>]` - Bump to the next patch version with a prerelease suffix.
 * `devmajor` - Bump to the next major version with a dev suffix.
 * `devminor` - Bump to the next minor version with a dev suffix.
 * `devpatch` - Bump to the next patch version with a dev suffix.
 * `prerelease [--preid=<prerelease-id>]` - Bump the prerelease suffix number.
 * `from-git` - Attempt to get the current version from `git`

If run in a git repo, it will also create a version commit and tag.

If `pre-version`, `pre-version-commit`,  or `post-version` are in the composer
[`scripts`](https://getcomposer.org/doc/articles/scripts.md) property, they will
be executed as part of running `composer version`.

The exact order of execution is as follows:

 1. Check to make sure the git working directory is clean before getting started.
 2. Run the `pre-version` script. These scripts have access to the old `version`
    in `composer.json`. Any files you want added to the commit should be 
    explicitly added using `git add`.
 3. Bump `version` in `composer.json` as requested.
 4. Run the `pre-version-commit` script. These scripts have access to the new
    `version` in `composer.json`. Again, scripts should explicitly add generated 
    files to the commit using `git add`.
 5. Commit and tag.
 6. Run the `post-version` script. Use it to clean up the file system or 
    automatically push the commit and/or tag.

### v1.0.0
This package will be considered `v1.0.0` once it has full feature parity with
[`npm version`](https://docs.npmjs.com/cli/version) (though some minor naming 
tweaks for `composer` compat), and a full set of tests.

A possible additional feature that may be added as part of `v1.0.0` will be the
ability to integrate w/ `npm version` to keep both `composer.json` and `package.json`
in sync for codebases that desire this.  

### License
This Source Code Form is subject to the terms of the Mozilla Public
License, v. 2.0. If a copy of the MPL was not distributed with this
file, You can obtain one at <https://mozilla.org/MPL/2.0/>.
