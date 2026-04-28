<?php
class ImageProcessorAI {

	public static $statusStr = '';

	/**
	 * Extract a primary key (catalog number) from a string (e.g. file name, catalogNumber field).
	 *
	 * - AI mode:
	 *   Sends the input string to an AI-based cleaner, optionally guided by an
	 *   example identifier. The AI returns a cleaned identifier, which is used
	 *   directly.
	 *
	 * param: str  String from which to extract the catalogNumber
	 * return: the extracted/cleaned identifier, or an empty string if extraction fails
	 */
	public static function getPrimaryKey($str, $example, $extra){
		$specPk = self::cleanWithAI($str, $example, $extra);

		if(!$specPk){
			self::$statusStr = 'AI failed to extract identifier from: ' . $str;
			return '';
		}

		self::$statusStr = 'AI cleaned identifier: "' . $str . '" => "' . $specPk . '"';

		return $specPk;
	}

	private static function cleanWithAI($value, $examplesRaw = '', $extraInstructions = '') {

		$exampleArr = array_filter(array_map('trim', explode("\n", $examplesRaw)));
		$exampleList = '"' . implode('", "', $exampleArr) . '"';

		$prompt = "Extract a cleaned identifier from this filename: \"$value\". Match the structure of these examples: $exampleList.";

		if($extraInstructions){
			$prompt .= ' ' . trim($extraInstructions);
		}

		$prompt .= " Return ONLY the identifier.";

		$response = self::callAI($prompt);
		return trim($response);
	}

	private static function callAI($prompt) {
		$apiKey = $GLOBALS['OPENAI_API_KEY'] ?? '';
		$model = $GLOBALS['OPENAI_MODEL'] ?? 'gpt-4.1-mini';

		if(!$apiKey){
			self::$statusStr = 'ERROR: OpenAI API key not configured';
			return '';
		}

		$data = [
				"model" => $model,
				"messages" => [
					["role" => "user", "content" => $prompt]
				]
		];

		$ch = curl_init("https://api.openai.com/v1/chat/completions");
		curl_setopt_array($ch, [
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTPHEADER => [
					"Authorization: Bearer $apiKey",
					"Content-Type: application/json"
				],
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => json_encode($data)
		]);

		$result = curl_exec($ch);
		curl_close($ch);

		$json = json_decode($result, true);

		if(isset($json['error'])){
			self::$statusStr = 'AI ERROR: '.$json['error']['message'];
			return '';
		}

		if(isset($json['usage'])){
			$usage = $json['usage'];
			self::$statusStr = 'AI TOKENS | prompt: ' . $usage['prompt_tokens'] .
				' | completion: ' . $usage['completion_tokens'] .
				' | total: ' . $usage['total_tokens'];
		}

		return $json['choices'][0]['message']['content'] ?? '';
	}
}
?>