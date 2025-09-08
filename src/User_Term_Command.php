<?php

use FP_CLI\CommandWithTerms;

/**
 * Adds, updates, removes, and lists user terms.
 *
 * ## EXAMPLES
 *
 *     # Set user terms
 *     $ fp user term set 123 test category
 *     Success: Set terms.
 */
class User_Term_Command extends CommandWithTerms {
	protected $obj_type = 'user';
}
