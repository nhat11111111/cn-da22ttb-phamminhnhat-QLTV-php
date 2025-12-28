<?php
/**
 * Lightweight MongoDB stubs for IDE/static analysis (intelephense, psalm).
 * These declarations are only for static typing and will not be active when
 * the real MongoDB extension is installed. They are intentionally minimal
 * and safe to include at runtime.
 */

namespace MongoDB\Driver {
	if (!class_exists('MongoDB\\Driver\\Manager')) {
		class Manager {
			public function __construct(string $uri = '', array $options = []) {}
			public function executeQuery(string $namespace, $query, array $options = []) {}
			public function executeBulkWrite(string $namespace, $bulk, array $options = []) {}
			public function executeCommand(string $db, $command, array $options = []) {}
		}
	}

	if (!class_exists('MongoDB\\Driver\\Query')) {
		class Query {
			public function __construct($filter = [], array $options = []) {}
		}
	}

	if (!class_exists('MongoDB\\Driver\\Command')) {
		class Command {
			public function __construct($command = []) {}
		}
	}

	if (!class_exists('MongoDB\\Driver\\BulkWrite')) {
		class BulkWrite {
			public function insert($document) {}
			public function insertOne($document) {}
			public function insertMany(array $documents) {}
			public function updateOne($filter, $update, array $options = []) {}
			public function updateMany($filter, $update, array $options = []) {}
			public function deleteOne($filter, array $options = []) {}
			public function deleteMany($filter, array $options = []) {}
			public function replaceOne($filter, $replacement, array $options = []) {}
		}
	}

	if (!class_exists('MongoDB\\Driver\\Cursor')) {
		class Cursor {
			public function current() {}
			public function key() {}
			public function next() {}
			public function rewind() {}
			public function valid() {}
			public function toArray() {}
		}
	}

	if (!class_exists('MongoDB\\Driver\\WriteResult')) {
		class WriteResult {
			public function getInsertedCount() {}
			public function getModifiedCount() {}
			public function getDeletedCount() {}
			public function getUpsertedCount() {}
		}
	}

	if (!class_exists('MongoDB\\Driver\\Exception')) {
		class Exception extends \RuntimeException {}
	}
	if (!class_exists('MongoDB\\Driver\\InvalidArgumentException')) {
		class InvalidArgumentException extends Exception {}
	}
	if (!class_exists('MongoDB\\Driver\\ProtocolException')) {
		class ProtocolException extends Exception {}
	}
	if (!class_exists('MongoDB\\Driver\\RuntimeException')) {
		class RuntimeException extends Exception {}
	}
}

namespace MongoDB {
	if (!defined('MongoDB\\BSON_TYPE_INT32')) define('MongoDB\\BSON_TYPE_INT32', 16);
	if (!defined('MongoDB\\BSON_TYPE_INT64')) define('MongoDB\\BSON_TYPE_INT64', 18);
	if (!defined('MongoDB\\BSON_TYPE_DOUBLE')) define('MongoDB\\BSON_TYPE_DOUBLE', 1);
	if (!defined('MongoDB\\BSON_TYPE_STRING')) define('MongoDB\\BSON_TYPE_STRING', 2);
	if (!defined('MongoDB\\BSON_TYPE_OBJECT')) define('MongoDB\\BSON_TYPE_OBJECT', 3);
	if (!defined('MongoDB\\BSON_TYPE_ARRAY')) define('MongoDB\\BSON_TYPE_ARRAY', 4);
	if (!defined('MongoDB\\BSON_TYPE_BINARY_DATA')) define('MongoDB\\BSON_TYPE_BINARY_DATA', 5);
}
