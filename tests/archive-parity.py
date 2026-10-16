"""Compare full archive loads with Municipio's real pagination endpoint."""

import argparse
import json
import ssl
from html.parser import HTMLParser
from urllib.parse import urlencode, urljoin
from urllib.request import urlopen


class ArchiveParser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.attributes = None
        self.cards = []
        self.depth = 0

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if "data-posts-list-attributes" in attrs:
            self.attributes = attrs["data-posts-list-attributes"]
        if tag == "a" and "data-js-posts-list-item" in attrs:
            self.cards.append([attrs["href"], []])
            self.depth = 1

    def handle_endtag(self, tag):
        if tag == "a":
            self.depth = 0

    def handle_data(self, data):
        if self.depth and data.strip():
            self.cards[-1][1].append(" ".join(data.split()))


parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument("url", help="Event archive URL")
parser.add_argument("--insecure", action="store_true", help="Local self-signed TLS")
args = parser.parse_args()
context = ssl._create_unverified_context() if args.insecure else ssl.create_default_context()


def read(url):
    with urlopen(url, context=context, timeout=30) as response:
        return response.read().decode()


for page in (1, 2):
    full = ArchiveParser()
    full.feed(read(args.url + "?" + urlencode({"archive_page": page})))
    assert full.attributes and full.cards, "Expected archive attributes and event cards"
    endpoint = urljoin(args.url, "/wp-json/municipio/v1/posts-list/render")
    partial = ArchiveParser()
    partial.feed(json.loads(read(endpoint + "?" + urlencode({
        "attributes": full.attributes,
        "archive_page": page,
    }))))
    assert full.cards == partial.cards, (page, full.cards, partial.cards)
    print(f"Page {page}: {len(full.cards)} identical links, dates and card texts")
