#!/usr/bin/env bash

BASE_URL="https://prestashop-8.2.3.ddev.site/module/askdialog"

API_TOKEN="NGQ2OWI5NTYtNjU1Yy00NjBhLTg4YTUtMmZlNGRjYmE2NWU4"
FEED_TOKEN="Y2RiNWJhMWMtNDlkNS00NDYxLThhOWEtN2ZkZGViOTY3NjJk"

case "$1" in
  get-catalog)
    curl -s "$BASE_URL/api?action=getCatalogData" \
      -H "Authorization: Token $API_TOKEN" | jq
    ;;
  get-language)
    curl -s "$BASE_URL/api?action=getLanguageData" \
      -H "Authorization: Token $API_TOKEN" | jq
    ;;
  get-product)
    if [ -z "$2" ]; then
      echo "Error: product ID required"
      echo "Usage: askd get-product <id_product>"
      exit 1
    fi
    curl -s "$BASE_URL/api?action=getProductData&id_product=$2" \
      -H "Authorization: Token $API_TOKEN" | jq
    ;;
  send-catalog)
    curl -s -X POST "$BASE_URL/feed?action=sendCatalogData" \
      -H "Authorization: Token $FEED_TOKEN"
    ;;
  *)
    echo "Usage:"
    echo "  askd get-catalog              Get full catalog data"
    echo "  askd get-language             Get language data"
    echo "  askd get-product <id>         Get single product data"
    echo "  askd send-catalog             Trigger async catalog export to S3"
    ;;
esac
