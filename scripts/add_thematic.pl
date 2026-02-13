#!/usr/bin/perl
#
# NAME
#
#  add_thematic.pl
#
# CONCEPT
#
#  Liberating Voices patterns on publicsphereproject.org each have an
#  associated thematice image. We've pulled those over to thematic/
#  and stored metadata. This script adds the images to the polymorphic
#  application.
#
# NOTES
#
#  Adding a feature to a template requires that the pattern_feature
#  table row be inserted (with the name and type) and that a
#  pt_feature row be inserted with the pattern_template.id and
#  pattern_feature.id values.  We do both here.
#
#  That done, adding the feature values to patterns requires inserting
#  a row in pf_image table with the corresponding pattern.id and
#  pattern_feature.id values along with the hash field that is needed
#  to find the image in the file system and the metadata fields.
#
#  The image tree is necessarily writable by the web server user,
#  which is the Unix user apache on Redhatian systems. For this script
#  to copy files into place, the simplest approach is to su to user
#  apache to run it.
#
#  The images are named with the title of the associated pattern and
#  stored in thematic/ with a .jpg extentions.
#
#  The datastore for the polymorphic application is MariaDB "pps'.
#  The relevant tables:
#
#   CREATE TABLE pattern_template (
#    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
#    name VARCHAR(255) NOT NULL UNIQUE,
#    ...
#   );
#   CREATE TABLE pattern (
#    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
#    ptid INT UNSIGNED NOT NULL,
#    ...
#   );
#   CREATE TABLE pattern_feature (
#    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
#    name VARCHAR(255) NOT NULL UNIQUE,
#    type ENUM('string', 'text', 'image', 'integer'),
#    ...
#   );
#   CREATE TABLE IF NOT EXISTS pt_feature (
#    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
#    ptid INT UNSIGNED NOT NULL,
#    fid INT UNSIGNED NOT NULL,
#    ...
#   );
#   CREATE TABLE `pf_image` (
#    id int(10) unsigned NOT NULL AUTO_INCREMENT,
#    pid int(10) unsigned NOT NULL,
#    pfid int(10) unsigned NOT NULL,
#    filename varchar(255) DEFAULT NULL,
#    alttext varchar(1023) NOT NULL,
#    hash char(40) NOT NULL,
#   );

use strict;
use DBI;
use Digest::SHA;
use Readonly;
use File::Copy;

our($destdb, $destuser, $destpw, $dbh, $sth, $patterns, $pfid, $ptid,
    $IMAGEDIR);

Readonly $IMAGEDIR => '/var/www/html/publicsphereproject/polymorphic/images';

use lib '.';
require 'creds.pl';
$dbh = DBI->connect("dbi:mysql:database=$destdb", $destuser, $destpw)
    or die 'connect';

# Get the value of pattern_feature.id for the thematicimage feature.
{
    my @row = $dbh->selectrow_array('SELECT id FROM pattern_feature WHERE name = "thematicimage"');
    $pfid = $row[0];
}
unless($pfid) {

    # The pattern_feature row for thematicimage doesn't exist; insert one.
    
    $dbh->do("INSERT INTO pattern_feature(name, type)
 VALUES('thematicimage', 'image')")
	or die 'insert pattern_feature';
    $pfid = $dbh->{mysql_insertid};
}

# Get the pattern values that use the LV template. We need pattern.id,
# pattern_template.id, and the pattern title.

$patterns = $dbh->selectall_hashref('
SELECT p.id, pt.id AS ptid, pfs.value AS title
 FROM pattern p
  JOIN pattern_template pt ON pt.id = p.ptid
  JOIN pt_feature ptf ON ptf.ptid = pt.id
  JOIN pattern_feature pf ON ptf.fid = pf.id
  JOIN pf_string pfs ON p.id = pfs.pid
 WHERE pt.name = "Liberating Voices" AND pf.name = "title"', 'title')
    or die 'selectall';

{
    # Get the value for pattern_template.id for 'Liberating Voices'.
    
    my $row = (values %$patterns)[0];
    $ptid = $row->{ptid};
}

# SQL to create a row in pf_image.

$sth = $dbh->prepare('INSERT INTO pf_image
  (pid, pfid, filename, alttext, hash)
 VALUES (?,?,?,?,?)');

# Look in the filesystem to find the image files.

opendir D, "thematic"
    or die 'opendir';
while(my $f = readdir(D)) {
    next
	unless($f =~ m/^(.+)\.jpg$/);
    my $title = $1;
    if(exists $patterns->{$title}) {
	my %insert;
	my $pattern = $patterns->{$title};

	# As expected, there is a pattern with this title. Generate the 
	# values we need for an insert.

	my $sfilename = "thematic/$f";
        my $sha = Digest::SHA->new;
	$sha->addfile($sfilename);
	$insert{hash} = $sha->hexdigest;
	$insert{filename} = $f;
	$insert{alttext} = "Generated alt text for $title; please fix";
	$insert{pid} = $pattern->{id};
	$insert{pfid} = $pfid;

	# Perform the insert.
	
	$sth->execute($insert{pid},$insert{pfid},$insert{filename},$insert{alttext},$insert{hash})
	    or die 'insert failed';

	# Copy the file, creating a subdirectory if neccesary.

	my $subdir = substr($insert{hash}, 0, 1);
	my $destdir = "$IMAGEDIR/$subdir";
	my $dfilename = "$destdir/$insert{hash}";

	unless(-d $destdir) {
	    unless(mkdir($destdir)) {
		die "mkdir($destdir) failed";
	    }
	}
	copy($sfilename, $dfilename)
	    or die "copy($sfilename, $dfilename) failed";
    } else {
	print "Found no pattern with title \"$title\"\n";
    }
}

{
    # Determine if a pt_feature row that ties the thematicimage feature to
    # the 'Liberating Voices' template exists.
    
    my @rows = $dbh->selectall_array("SELECT * FROM pt_feature
 WHERE ptid = $ptid AND fid = $pfid");

    unless(scalar @rows) {
	
	# The pt_feature rows for thematicimage don't exist; insert.
		 
	$dbh->do("INSERT INTO pt_feature(ptid, fid)
 VALUES(1, $pfid)")
	    or die 'insert pt_feature';
	$dbh->do("INSERT INTO pt_feature(ptid, fid)
 VALUES(2, $pfid)")
	    or die 'insert pt_feature';
    }
}
