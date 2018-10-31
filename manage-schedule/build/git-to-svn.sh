#!/bin/bash

# This build script is called from a Jenkins job. 
# When I push my local git repository to Github, Jenkins automatically commits it to SVN repo on WordPress.org.

# Removes the last trunk dir from local SVN
rm -rf /media/sf_D_DRIVE/Dropbox/Producao/publish-to-schedule/trunk/

# Re-crates trunk
mkdir /media/sf_D_DRIVE/Dropbox/Producao/publish-to-schedule/trunk

# Clone repository from GitHub
git clone https://github.com/alexbenfica/Publish-to-Schedule-WordPress-plugin.git /media/sf_D_DRIVE/Dropbox/Producao/publish-to-schedule/trunk/

# Removes git files from SVN trunk
rm -rf /media/sf_D_DRIVE/Dropbox/Producao/publish-to-schedule/trunk/.git

# Still can't make it work without typing password... so call a script to commit to SVN using password in command line.
sh /media/sf_D_DRIVE/DevelOffline/publish-to-schedule/commit-to-svn.sh