#!/usr/bin/perl
#
# NAME
#
#  thematic.pl
#
# CONCEPT
#
#  Get data about thematic images from www.publicsphereproject.org/patterns/lv.
#
# NOTES
#
#  LV pattern summaries are grouped in HTML tables under /patterns/lv/.
#  Each summary has, among other things, a small thematic image and a
#  link to a per-pattern page. The per-pattern page has an IMG tag
#  that selects the full-size image we want. On the pattern summary
#  pages, there are links to the other pattern summary pages that are
#  of the form /lv/pattern?page=n, where n is a small sequential integer
#  in the set [1..6]. The page at /patterns/lv/ can also be accessed as
#  /patterns/lv/page=0. Our strategy is to mine the pattern summary
#  pages for the links to the per-pattern pages, then visit them to
#  mine the src attributes of those IMG tags, then store that information
#  in an SQLite3 database so we can fetch the images in a separate process.
#
#  For the sake of efficiency, all we do here is fetch and store the
#  metadata. Fetching the images is a separate process, as is loading
#  them into the polymorphic/ applicattion.

use strict;
use HTML::TreeBuilder;
use Readonly;
use DBI;

our($BASE, %ppages, $dbh, $sth);
Readonly $BASE => 'https://publicsphereproject.org';

# Loop on pages, of which we know there are 7.

for my $page (0..6) {

    # Fetch this pattern summary page.
    
    my $url = $BASE . "/patterns/lv?page=$page";
    my $tb = HTML::TreeBuilder->new_from_url($url);

    #  Get the table element.

    my @tables = $tb->find('table');
    my $table = $tables[0];

    # Get the links from the table. Expect up to 20.

    my @links = $table->find('a');
    for my $link (@links) {
	my @content = $link->content_list;
	my $anchor = $content[0];
	$ppages{$anchor} = {
	    'url' => $link->attr('href'),
	    'anchor' => $anchor
	};
    }
}

# We now have all the URLs of pattern pages.

print 'Found ', scalar(%ppages), " pattern page links.\n";

# Visit each pattern page to fetch the image src attribute.

for my $ppage (values %ppages) {
    my $url = $BASE . $ppage->{url};
    my $tb = HTML::TreeBuilder->new_from_url($url);

    # Get the IMG elements. The one we want is the second.

    my @img = $tb->find('img');
    $ppage->{'src'} = $img[1]->attr('src');
    print "$ppage->{anchor}\n";
}

# Store the metadata.

$dbh = DBI->connect('dbi:SQLite:thematic/thematic.db', '', '')
    or die 'connect';
$dbh->do('CREATE TABLE IF NOT EXISTS thematic (
  title NOT NULL,
  src NOT NULL
)')
    or die 'create table';
$sth = $dbh->prepare('INSERT INTO thematic (title, src) VALUES (?, ?)')
    or die 'prepare';

for my $ppage (values %ppages) {
    $sth->execute($ppage->{anchor}, $ppage->{src})
	or die 'execute';
}
