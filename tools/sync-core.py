#!/usr/bin/env python3
"""Generate the GPL formatter from the reviewed WordPress source; no runtime dependency."""
from pathlib import Path
import argparse, hashlib, re, subprocess
p=argparse.ArgumentParser();p.add_argument('source',type=Path);p.add_argument('--revision',required=True);a=p.parse_args()
s=a.source.read_text();start=s.index('function wptexturize(');end=s.rfind('/**',start,s.index('function wptexturize_primes('))
code=s[start:end]
for old,new in [('_wptexturize_get_text_context','text_context'),('_wptexturize_get_inline_tag_type','inline_tag_type'),('wptexturize','format')]:
 code=re.sub(r'\b'+old+r'\b',new,code)
# Qualify global calls so PHP can retain its builtin fast paths in the namespace.
php = r"""$tokens = token_get_all(stream_get_contents(STDIN));
$out = ''; $previous = null;
foreach ($tokens as $index => $token) {
    if (is_array($token) && T_STRING === $token[0]) {
        $next = $index + 1;
        while (isset($tokens[$next]) && is_array($tokens[$next]) && in_array($tokens[$next][0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true)) { ++$next; }
        if (isset($tokens[$next]) && '(' === $tokens[$next] && T_FUNCTION !== $previous && !in_array($token[1], array('format', 'text_context', 'inline_tag_type'), true)) { $token[1] = '\\' . $token[1]; }
    }
    $out .= is_array($token) ? $token[1] : $token;
    if (!(is_array($token) && in_array($token[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true))) { $previous = is_array($token) ? $token[0] : $token; }
}
echo substr($out, 6);"""
code = subprocess.run(['php','-r',php], input='<?php\n'+code, text=True, capture_output=True, check=True).stdout
# These are core-owned translation keys and extension hooks, not plugin strings.
# Explicitly retain the core domain, and narrowly document core-hook reuse.
code = re.sub(r"(\\_x\( '[^'\n]*', '[^'\n]*') \)", r"\1, 'default' )", code)
code = code.replace("'Comma-separated list of words to texturize in your language'", "'Comma-separated list of words to texturize in your language',\n\t\t\t\t\t'default'")
code = code.replace("'Comma-separated list of replacement words in your language'", "'Comma-separated list of replacement words in your language',\n\t\t\t\t\t'default'")
header='''<?php
/**
 * Generated WordPress formatter carrying Trac #18549 / #43810.
 *
 * Derived from WordPress wp-includes/formatting.php, copyright WordPress contributors.
 * GPL-2.0-or-later. See LICENSE. Do not edit; use tools/sync-core.py.
 * Source revision: %s
 * Source SHA-256: %s
 *
 * @package WP_Texturize_Inline_Quote_Fix
 */

namespace DK\\Texturize;

if ( ! defined( 'ABSPATH' ) ) {
\texit;
}

// Core hooks and translation keys must retain their native contracts.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
// phpcs:disable WordPress.WP.I18n.TextDomainMismatch

'''%(a.revision,hashlib.sha256(a.source.read_bytes()).hexdigest())
(Path(__file__).resolve().parent.parent/'includes/formatter.php').write_text(header+code.rstrip()+'\n')
