#!/bin/zsh

#Fetch remote tags
git fetch origin 'refs/tags/*:refs/tags/*'

#Variables
LAST_VERSION=$(git tag -l | sort -t. -k 1,1n -k 2,2n -k 3,3n -k 4,4n | tail -n 1)
NEXT_VERSION=$(echo $LAST_VERSION | awk -F. -v OFS=. 'NF==1{print ++$NF}; NF>1{if(length($NF+1)>length($NF))$(NF-1)++; $NF=sprintf("%0*d", length($NF), ($NF+1)%(10^length($NF))); print}')
VERSION=${1-${NEXT_VERSION}}
DEFAULT_MESSAGE="Release"
MESSAGE=${2-${DEFAULT_MESSAGE}}
RELEASE_BRANCH="release/$VERSION"

# Commit uncommited changes
git add .
git commit -am $MESSAGE
git push origin develop

# Merge develop branch in master
git checkout master
git merge develop

# Tag and push master
git tag $VERSION
git push origin master --tags

# Return to develop
git checkout develop
