<?php
// Model: User account management (local storage in Taikhoan.html)

declare(strict_types=1);

class AccountRepository {
    /** @var string|null Path to `frontend/views/Taikhoan.html` */
    private $htmlFile;
    
    public function __construct($htmlFile = null) {
        if (!$htmlFile) {
            $htmlFile = realpath(__DIR__ . '/../../frontend/views/Taikhoan.html');
        }
        $this->htmlFile = $htmlFile;
    }

    /**
     * Return resolved path to the Taikhoan.html used by this repository.
     * Useful for debugging tools instead of using reflection.
     *
     * @return string|null
     */
    public function getHtmlFile(): ?string {
        return $this->htmlFile;
    }
    
    public function readAccounts(): array {
        if (!$this->htmlFile || !file_exists($this->htmlFile)) {
            return [];
        }
        
        $html = @file_get_contents($this->htmlFile);
        if ($html === false) {
            return [];
        }
        
        // Extract JSON from <script id="savedAccounts">
        $matches = [];
        if (!preg_match('#<script[^>]*id=["\']savedAccounts["\'][^>]*>(.*?)</script>#is', $html, $matches)) {
            return [];
        }
        
        $json = trim($matches[1] ?? '');
        $accounts = json_decode($json, true);
        if (!is_array($accounts)) {
            return [];
        }

        // If any account passwords are stored in plaintext, hash them and persist
        $updated = false;
        foreach ($accounts as &$acc) {
            $pwd = $acc['password'] ?? '';
            if ($pwd !== '' && !preg_match('/^\$2[ayb]\$|^\$argon2/', $pwd)) {
                $acc['password'] = password_hash($pwd, PASSWORD_DEFAULT);
                $updated = true;
            }
        }
        unset($acc);

        if ($updated) {
            $this->writeAccounts($accounts);
        }

        return $accounts;
    }
    
    public function addAccount(string $username, string $password): bool {
        if (!$this->htmlFile) {
            return false;
        }
        
        // Ensure file exists
        if (!file_exists($this->htmlFile)) {
            @file_put_contents($this->htmlFile, '');
        }
        
        $accounts = $this->readAccounts();
        
        // Check for duplicate
        foreach ($accounts as $a) {
            if (isset($a['username']) && $a['username'] === $username) {
                return false;
            }
        }
        
        // Store hashed password
        $accounts[] = ['username' => $username, 'password' => password_hash($password, PASSWORD_DEFAULT)];
        return $this->writeAccounts($accounts);
    }
    
    private function writeAccounts(array $accounts): bool {
        if (!$this->htmlFile || !file_exists($this->htmlFile)) {
            return false;
        }
        
        $html = @file_get_contents($this->htmlFile);
        if ($html === false) {
            return false;
        }
        
        $newJson = json_encode($accounts, JSON_UNESCAPED_UNICODE);
        
        if (preg_match('#(<script[^>]*id=["\']savedAccounts["\'][^>]*>)(.*?)(</script>)#is', $html)) {
            $newHtml = preg_replace(
                '#(<script[^>]*id=["\']savedAccounts["\'][^>]*>)(.*?)(</script>)#is',
                '$1' . $newJson . '$3',
                $html,
                1
            );
        } else {
            // Append script before </body>
            $newHtml = preg_replace(
                '#</body>#i',
                "<script id=\"savedAccounts\" type=\"application/json\">" . $newJson . "</script>\n</body>",
                $html,
                1
            );
        }
        
        return @file_put_contents($this->htmlFile, $newHtml) !== false;
    }
}
