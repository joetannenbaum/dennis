#!/bin/bash

latestTag=$(git describe --tags `git rev-list --tags --max-count=1`)

read -p "Version number (current is $latestTag):" version

# mv .env .env.bak
./dennis app:build --build-version=$version
# mv .env.bak .env

if [[ $(git status --porcelain) ]]; then
    git add builds/dennis
    git commit -m "Release $version"
    git push
fi

git push
git tag -a $version -m "$version"
git push --tags
echo "\n"
echo "https://github.com/joetannenbaum/dennis/releases"