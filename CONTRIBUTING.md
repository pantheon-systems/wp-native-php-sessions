# Contributing

The best way to contribute to the development of this plugin is by participating on the GitHub project:

https://github.com/pantheon-systems/wp-native-php-sessions

Pull requests and issues are welcome!

## Testing

You may notice there are two sets of tests running, on two different services:

* The [PHPUnit](https://phpunit.de/) test suite.
* The [Behat](http://behat.org/) test suite runs against a Pantheon site, to ensure the plugin's compatibility with the Pantheon platform.

Both of these test suites can be run locally, with a varying amount of setup.

PHPUnit requires the [WordPress PHPUnit test suite](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/), and access to a database with name `wordpress_test`. If you haven't already configured the test suite locally, you can run `composer test:install:withdb`. Otherwise, you can install the test suite with `composer test:install`, and run the tests with `composer test`.

Behat requires a Pantheon site. Once you've created the site, you'll need [install Terminus](https://github.com/pantheon-systems/terminus#installation), and set the `TERMINUS_TOKEN`, `TERMINUS_SITE`, and `TERMINUS_ENV` environment variables. Then, you can run `./bin/behat-prepare.sh` to prepare the site for the test suite.

## Workflow

Development and releases are structured around two branches, `main` and `release`. The `main` branch is the source and destination for feature branches.

We prefer to squash commits (i.e. avoid merge PRs) from a feature branch into `main` when merging, and to include the PR # in the commit message. PRs to `main` should also include any relevent updates to the changelog in readme.txt. If a feature constitutes a minor or major version bump, that version update should be discussed and made as part of approving and merging the feature into `main`.

`main` should be stable and usable, though will be few commits ahead of the public release on wp.org.

The `release` branch matches the latest stable release deployed to [wp.org](wp.org). Releases are shipped from tags created on push to the `release` branch.

## Release Process

1. Merge your feature branch into `main` with a PR. This PR should include any necessary updates to the changelog in readme.txt and README.md. Features should be squash merged. 
1. From main, checkout a new branch `release_X.Y.Z`.
1. Make a release commit: 
    * In `package.json`, `README.md`, `readme.txt`, and `pantheon-sessions.php`, remove the `-dev`  from the version number. 
    * For the README files, the version number must be updated both at the top of the document as well as the changelog. 
    * Add the date to the  `** X.Y.Z **` heading in the changelogs in `README.md`, `readme.txt`, and any other appropriate location. 
    * Commit these changes with the message `Release X.Y.Z`
    * Push the release branch up.
1. Open a Pull Request to merge `release_X.Y.Z` into `release`. Your PR should consist of all commits to `main` since the last release, and one commit to update the version number. The PR name should also be `Release X.Y.Z`.
1. After all tests pass and you have received approval from a CODEOWNER (including resolving any merge conflicts), merge the PR into `release`. Use a "merge" commit, do no not rebase or squash. If the GitHub UI doesn't offer a "Merge commit" option (only showing "Squash and merge" or "Rebase and merge"), merge from the terminal instead:
    `git checkout release`
    `git merge release_X.Y.Z`
    `git push origin release`
1. After merging to the `release` branch, a draft Release will be automatically created by the build-tag-release workflow. This draft release will be automatically pre-filled with release notes. 
1. Confirm that the necessary assets are present in the newly created tag, and test on a WP install if desired. 
1. Review the release notes, making any necessary changes, and publish the release. 
1. Wait for the Release plugin to wp.org action to finish deploying to the WordPress.org plugin repository.
1. If all goes well, users with SVN commit access for that plugin will receive an email with a diff of the changes. 
1. Check WordPress.org: Ensure that the changes are live on the plugin repository. This may take a few minutes.
1. Following the release, prepare the next dev version with the following steps:
    * `git checkout release`
    * `git pull origin release`
    * `git checkout main`
    * `git rebase release`
    * Update the version number in all locations, incrementing the version by one patch version, and add the `-dev` flag (e.g. after releasing `1.2.3`, the new verison will be `1.2.4-dev`)
    * Add a new `** X.Y.Z-dev **` heading to the changelog
    * `git add -A .`
    * `git commit -m "Prepare X.Y.Z-dev"`
    * `git checkout -b release-XYZ-dev`
    * `git push origin release-XYZ-dev`
    * Create a pull request on GitHub UI from `release-XYZ-dev` to `main` to trigger all required status checks
    * _Wait for all required status checks to pass in CI. Once all tests pass, push to main from the terminal:_
    * `git checkout main && git push origin main`
    * _Note: While main is typically protected, having an open PR with passing tests allows direct push to main, which is the preferred method here._


## Asset-only Releases
> ⚠️ WARNING: This workflow has some pitfalls and may need further adjustment. Tread carefully if attempting an asset-only update and don't be surprised if it takes multiple attempts.

Thanks to [10up/action-wordpress-plugin-asset-update](https://github.com/10up/action-wordpress-plugin-asset-update/) we can make asset-only updates to WordPress.org without needing to create a new release. This is useful for updating the plugin banner, icon, screenshots or just updating the readme.txt.

Broadly the process for creating asset-only releases is as follows:

1. Branch off of `release` (not `main`) and make your changes. Ensure that you are _only_ making changes to `readme.txt`/`readme.md` or files in the `.wordpress.org` directory. Some other changes (e.g. to `.gitignore` or `composer.json`) are allowed but any file changes to anything beyond those locations will trigger the release automation.
1. Push your branch to GitHub and open a PR against `release`.
1. Once the PR is merged, the asset update action will run and update the assets on WordPress.org.
1. Check out `main` and merge `release` into it to ensure that the asset-only changes are included in the next release.

**Note:** In the future we will work on making this process smoother and more automated.