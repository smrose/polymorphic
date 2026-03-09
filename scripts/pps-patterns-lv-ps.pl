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
#  contains only the pattern id and pattern_template id
#
#  pattern features are defined in the pattern_feature table
#
#  rows in the pt_feature table establish which features a pattern may
#  or must have
#
#  pattern feature values are in tables named for the feature type
#
#  pattern authors are represented in the author and pattern_author tables

use strict;
use DBI;
use Readonly;
use Try::Tiny;

our($dbh, $sth, $sth2, $patterns, $pattern_template, $pattern_language,
    %psid2ppsid, $pattern_features, @pf, $sourcedb, $destdb, $sourceuser,
    $destuser, $sourcepw, $destpw, $authors, $pattern_authors, %psa2ppsa);

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

    # create the feature values
    
    for my $pattern_feature (values %$pattern_features) {
	my $pftype = $pattern_feature->{type};
	my $pfname = $pattern_feature->{name};
	next
	    unless( defined $pattern->{$pfname});
	my $sth =
	    $dbh->prepare("INSERT INTO pf_$pftype (pid, pfid, value) VALUES (?, ?, ?)");
	try {
	    $sth->execute($pid, $pattern_feature->{id}, $pattern->{$pfname})
	} catch {
	    print "INSERT failed for $pfname";
	}
    }
    return $pid;

} # end pinsert()

# Fetch the patterns from ps.

$dbh = DBI->connect('dbi:mysql:database=' . $sourcedb, $sourceuser, $sourcepw)
    or die 'connect';
$patterns = $dbh->selectall_hashref('SELECT p.* FROM pattern p
  JOIN planguage pl ON p.plid = pl.id
 WHERE pl.title = "Liberating Voices"', 'id')
    or die 'selectall ps.pattern';

# Fetch all the author records from ps.

$authors = $dbh->selectall_hashref('SELECT * FROM author', 'id')
    or die 'selectall ps.author';

# Fetch the pattern_author records from ps that correspond to a LV pattern.

$pattern_authors = $dbh->selectall_arrayref('SELECT author_id, pattern_id
  FROM pattern_author pa
    JOIN pattern p ON pa.pattern_id = p.id
    JOIN planguage pl ON p.plid = pl.id
  WHERE pl.title = "Liberating Voices"', {Slice=>{}})
    or die 'selectall ps.pattern_author';

$dbh->disconnect;

# Connect to pps.

$dbh = DBI->connect('dbi:mysql:database=' . $destdb, $destuser, $destpw)
    or die 'connect';

# Get the pattern_template id.

$pattern_template = $dbh->selectcol_arrayref('SELECT id
 FROM pattern_template
 WHERE name = "Liberating Voices"');
$pattern_template = $pattern_template->[0];
# Get the pattern features.

# Get the pattern_features from pps.

$pattern_features = $dbh->selectall_hashref('SELECT * FROM pattern_feature', 'id')
    or die 'selectall pps.pattern_feature';

# Get the pattern_language id.

$pattern_language = $dbh->selectcol_arrayref('SELECT id FROM pattern_language');
$pattern_language = $pattern_language->[0];

# Insert each pattern, creating a map of ps.pattern.id to pps.pattern.id.

for my $pattern (sort {$a->{id} <=> $b->{id}} values %$patterns) {
    $psid2ppsid{$pattern->{id}} = pinsert($pattern);
}

# Insert the pattern_author and due author records.

$sth = $dbh->prepare('INSERT INTO pattern_author (pattern_id, author_id) VALUES (?, ?)')
    or die 'prepare INSERT pattern_author';
$sth2 = $dbh->prepare('INSERT INTO author (name, affiliation) VALUES (?, ?)')
    or die 'prepare INSERT author';

for my $pa (@$pattern_authors) {
    my $pspid = $pa->{pattern_id};    # ps.pattern.id
    my $ppspid = $psid2ppsid{$pspid}; # pps.pattern.id
    my $aid = $pa->{author_id};       # ps.author.id
    if(! exists $psa2ppsa{$aid}) {

	# Insert new author.
	
	$sth2->execute($authors->{$aid}->{name}, $authors->{$aid}->{affiliation})
	    or die "INSERT author $authors->{name} failed";
	$psa2ppsa{$aid} = $dbh->{mysql_insertid};
    }
    my $ppsa = $psa2ppsa{$aid};
    $sth->execute($ppspid, $ppsa)
	or die "INSERT pattern_author $pspid, $aid failed";
}
