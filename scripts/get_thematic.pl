#!/usr/bin/perl
#
# NAME
#
#  get_thematic.pl
#
# CONCEPT
#
#  Pull thematic images from www.publicspherepronect.org/patterns/lv.
#
# NOTES
#
#  We have a SQLite database with the metadata:
#
#   CREATE TABLE thematic (
#    title NOT NULL,
#    src NOT NULL
#   );
#
#  Fetch those images into thematic/.

use strict;
use DBI;
use LWP::Simple;

our($dbh, $images);

# Fetch the metatdata.

$dbh = DBI->connect('dbi:SQLite:thematic/thematic.db', '', '')
    or die 'connect';
$images = $dbh->selectall_hashref('SELECT * FROM thematic', 'title')
    or die 'selectall_hashref';

for my $image (values %$images) {
    my $content = get($image->{src})
	or die "get $image->{title}";
    open F, '>', "thematic/$image->{title}.jpg"
	or die "open $image->{title}";
    print F $content;
}

