# Release documentation

### Pre-requisites
In order to release a new version of the module, you will need the following:

1. Necessary permissions to the Github repository to create tags and releases.
2. All the required code is merged to target branch.
3. Ensure that the module's [CHANGELOG.md](CHANGELOG.md) file has been updated to contain details of the release you are planning to make.
4. Clone of the Github repository in a local computer (local repo).

You should then follow these steps:

## 1. Merge necessary Pull Requests
Before creating a release, check that all pull requests are merged into the agreed target branch, usually "master".

## 2. Draft a release in Github
Create a release by clicking on "Draft a new release".

Enter new release version number to "Choose a tag" box, the same number as in the project's **composer.json** file, for example "1.0.1".

For the Target, choose the "master" branch or a different branch if it was previously agreed.

For Release title, enter something more user friendly, for example "Version 1.0.1"

Leave release description blank for now.

Click on the "Publish release" button.

## 3. Create dist
In local repo, enter following commands to create a dist zip file, in the example 1.0.1 is used, make sure this is replaced by the correct tag name

```
git fetch --tags --all
git archive 1.0.1 -o module-dist.zip
```

Created file module-dist.zip contains the distributable code for this module.

## 4. Add assets and finalise the release
In the Github UI, switch to edit the release you created in step 2. The URL will contain something like /edit/1.0.1, for example.

Upload the module-dist.zip into the designated area in the release edit page.

Populate the release description with the information about the version that you can find in the [CHANGELOG.md](CHANGELOG.md) file.

Click on the "Update release" button, and inform the development team that the release has been published.
