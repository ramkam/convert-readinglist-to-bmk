#!/bin/bash
ts="$(date "+%Y-%m-%d_%H-%M-%S")"

workdir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"; 
	# the script's working directory, 
	# http://stackoverflow.com/questions/59895/can-a-bash-script-tell-what-directory-its-stored-in

if [ ! -d "$workdir" ]; then
	echo "#ERR - workdir does not exit - stopping - $workdir";
	exit 1;
fi

# Ensure control of files position 
cd "$workdir"

# Copy  (no brackets if using ~/ ??? ... works like this anyway)
cp -f ~/Library/Safari/Bookmarks.plist "$workdir/Bookmarks.plist"

# Analyse
php -f extract_reading_list.php | tee "$workdir/report.txt"

# Archive report
mkdir -p "$workdir/archive_reports"
mv "$workdir/report.txt" "$workdir/archive_reports/${ts} - report.txt"

# Archive before clearing reading list
mkdir -p "$workdir/archive_bookmarks"
mv "$workdir/Bookmarks.plist" "$workdir/archive_bookmarks/${ts} - Bookmarks.plist"


# Cleanup (if no errors)
# //TBD//
clear;
echo "Done !";
echo "";
echo "#------------------------------------------------------------------#";
echo "# (!) Dont forget to clear manually the reading list in safari (!) #";
echo "#------------------------------------------------------------------#"

