#!/usr/bin/perl
#
# NAME
#
#  pps-patterns-lv-ps.pl
#
# CONCEPT
#
#  Add Liberating Voices patterns, reading from 'ps' and writing to 'pps'.
#
# NOTES
#
#  ps.pattern is a table with one row per pattern. pps.pattern
#  contains only the pattern id and pattern_template id, and all the
#  pattern features live in the pattern_feature table while all the
#  pattern feature values are in tables named for the feature.
#
#  CREATE TABLE IF NOT EXISTS pattern (
#   id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
#   pt_id INT UNSIGNED NOT NULL,
#   CONSTRAINT FOREIGN KEY (ptid) REFERENCES pattern_template(id)
#  );
#
#  CREATE TABLE plmember (
#   pid integer unsigned NOT NULL,
#   plid integer unsigned NOT NULL,
#   FOREIGN KEY (pid) REFERENCES pattern(id),
#   FOREIGN KEY (plid) REFERENCES pattern_language(id)
#  );

use strict;
use DBI;
use Readonly;
use Try::Tiny;

our($dbh, $sth, $patterns, $pattern_template, $pattern_language,
    $pattern_features, @pf, $sourcedb, $destdb, $sourceuser,
    $destuser, $sourcepw, $destpw);

use lib '.';
require 'creds.pl';

# pinsert()
#
#  Insert one pattern into pps: a row in 'pattern', a row in 'plmember',
#  and a row in each feature value table.
#
#  pattern_features have been established, and tables for all the columns
#  have been created.

sub pinsert {
    my $pattern = $_[0];
    
    print "$pattern->{id}\t$pattern->{title}\n";

    # create the pattern record
    
    $dbh->do("INSERT INTO pattern (ptid) VALUES ($pattern_template)")
	or die 'insert pattern';
    my $pid = $dbh->{mysql_insertid};

    # create the plmember record

    $dbh->do("INSERT INTO plmember(pid, plid) VALUES ($pid, $pattern_language)")
	or die 'insert plmember';

    # create the features
    
    for my $pattern_feature (values %$pattern_features) {
	my $pfname = $pattern_feature->{name};
	next
	    unless( defined $pattern->{$pfname});
	my $sth =
	    $dbh->prepare("INSERT INTO pf_$pfname (pid, pfid, value) VALUES (?, ?, ?)");
	try {
	    $sth->execute($pid, $pattern_feature->{id}, $pattern->{$pfname})
	} catch {
	    print "INSERT failed for $pfname";
	}
    }

} # end pinsert()

# Fetch the patterns from ps.

$dbh = DBI->connect('dbi:mysql:database=' . $sourcedb, $sourceuser, $sourcepw)
    or die 'connect';
$patterns = $dbh->selectall_hashref('SELECT p.* FROM pattern p
  JOIN planguage pl ON p.plid = pl.id
 WHERE pl.title = "Liberating Voices"', 'id')
    or die 'selectall ps.pattern';
$dbh->disconnect;

# Get the pattern_features from pps.

$dbh = DBI->connect('dbi:mysql:database=' . $destdb, $destuser, $destpw)
    or die 'connect';

# Get the pattern_template id.

$pattern_template = $dbh->selectcol_arrayref('SELECT id
 FROM pattern_template
 WHERE name = "Liberating Voices"');
$pattern_template = $pattern_template->[0];
# Get the pattern features.

$pattern_features = $dbh->selectall_hashref('SELECT * FROM pattern_feature', 'id')
    or die 'selectall pps.pattern_feature';

# Get the pattern_language id.

$pattern_language = $dbh->selectcol_arrayref('SELECT id FROM pattern_language');
$pattern_language = $pattern_language->[0];

for my $pattern (sort {$a->{id} <=> $b->{id}} values %$patterns) {
    pinsert($pattern);
}
