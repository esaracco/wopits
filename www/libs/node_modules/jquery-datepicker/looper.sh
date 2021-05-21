#!/bin/bash
for filename in ./test/*.js; do
    echo "prepending $filename"
    echo "export default function(jQuery) {"|cat - $filename > /tmp/out && mv /tmp/out $filename
    echo "appending $filename"
    echo "};" >> $filename
done