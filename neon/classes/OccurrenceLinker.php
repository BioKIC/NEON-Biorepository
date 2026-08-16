<?php

require_once(__DIR__ . '/extendedSpecimenLinks/FishProtocol.php');

class OccurrenceLinker {

    private mysqli $conn;
    private string $protocolJsonPath;
    private $logger;
    private array $protocolConfig = [];

    public function __construct(mysqli $conn, string $protocolJsonPath, ?callable $logger = null) {
        $this->conn = $conn;
        $this->protocolJsonPath = $protocolJsonPath;
        $this->logger = $logger;
        $this->loadProtocolConfig();
    }

    /**
     * Main entry point for nightly processing.
     */
    public function run(): void {
        $this->runFishProtocol();

        /*
         * Later:
         * $this->runBeetleProtocol();
         * $this->runMosquitoProtocol();
         * $this->runPlantProtocol();
         * etc.
         */
    }

    private function runFishProtocol(): void {
        $protocolID = 37;
        $protocol = $this->findProtocolById($this->protocolConfig, $protocolID);

        if (!$protocol) {
            $this->log("Fish protocol {$protocolID} not found in protocol JSON");
            return;
        }

		$collections = $this->getCollectionsRecursive($protocol);
		
		if (!$collections) {
			$this->log("No collections found for Fish protocol {$protocolID}");
			return;
		}
		
		$collids = array_column($collections, 'collid');
		
		$collectionList = array_map(function ($collection) {
			return $collection['name'] . ' (' . $collection['collid'] . ')';
		}, $collections);
		
		$this->log('Starting Fish extended specimen linkage; collections: ' . implode(', ', $collectionList));
		
		$linker = new FishProtocolLinker($this->conn, $collids, $this->logger);
		$linker->run();
    }

    /**
     * Load protocol JSON.
     */
    private function loadProtocolConfig(): void {
        if (!file_exists($this->protocolJsonPath)) {
            throw new RuntimeException('Protocol JSON not found: ' . $this->protocolJsonPath);
        }

        $json = file_get_contents($this->protocolJsonPath);

        if ($json === false) {
            throw new RuntimeException('Unable to read protocol JSON: ' . $this->protocolJsonPath);
        }

        $config = json_decode($json, true);

        if (!is_array($config)) {
            throw new RuntimeException('Invalid protocol JSON: ' . json_last_error_msg());
        }

        $this->protocolConfig = $config;
    }

    /**
     * Find a protocol by its JSON id.
     */
	private function findProtocolById(array $protocols, int $id): ?array {
		foreach ($protocols as $protocol) {
			if (isset($protocol['id']) && (int)$protocol['id'] === $id) {
				return $protocol;
			}
		}
	
		return null;
	}

    /**
     * Get every collid underneath a protocol.
     * Works regardless of how deeply children are nested.
     */
	private function getCollectionsRecursive(array $node): array {
		$collections = [];
	
		if (isset($node['collid']) && is_numeric($node['collid'])) {
			$collections[] = [
				'collid' => (int)$node['collid'],
				'name' => $node['name'] ?? ''
			];
		}
	
		if (!empty($node['children'])) {
			foreach ($node['children'] as $child) {
				if (!is_array($child)) {
					continue;
				}
	
				$collections = array_merge($collections, $this->getCollectionsRecursive($child));
			}
		}
	
		return $collections;
	}

    private function log(string $message): void {
        if ($this->logger) {
            call_user_func($this->logger, $message);
        }
    }
}