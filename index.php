<?php
class KnowledgeBase {
    private $data;
    private $currentTheme;
    private $currentSubtheme;
    
    public function __construct() {
        $this->data = [
            'Тема 1' => [
                'Подтема 1.1' => 'Текст 1.1',
                'Подтема 1.2' => 'Текст 1.2',
                'Подтема 1.3' => 'Текст 1.3'
            ],
            'Тема 2' => [
                'Подтема 2.1' => 'Текст 2.1',
                'Подтема 2.2' => 'Текст 2.2',
                'Подтема 2.3' => 'Текст 2.3'
            ],
            'Тема 3' => [
                'Подтема 3.1' => 'Текст 3.1',
                'Подтема 3.2' => 'Текст 3.2',
                'Подтема 3.3' => 'Текст 3.3'
            ]
        ];
        
        // Установка текущей темы и подтемы
        $this->currentTheme = $_GET['theme'] ?? 'Тема 1';
        $this->currentSubtheme = $_GET['subtheme'] ?? array_key_first($this->data[$this->currentTheme]);
    }
    
    public function getThemes() {
        return array_keys($this->data);
    }
    
    public function getSubthemes($theme) {
        return array_keys($this->data[$theme] ?? []);
    }
    
    public function getContent($theme, $subtheme) {
        return $this->data[$theme][$subtheme] ?? 'Содержимое не найдено или не выбрана подтема';
    }
    
    public function getCurrentTheme() {
        return $this->currentTheme;
    }
    
    public function getCurrentSubtheme() {
        return $this->currentSubtheme;
    }
    
    public function isCurrentTheme($theme) {
        return $this->currentTheme === $theme;
    }
    
    public function isCurrentSubtheme($subtheme) {
        return $this->currentSubtheme === $subtheme;
    }
}

$knowledgeBase = new KnowledgeBase();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>База знаний</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .container {
            display: flex;
            gap: 20px;
        }
        .column {
            flex: 1;
            border: 1px solid #ddd;
            padding: 10px;
            min-height: 300px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .clickable {
            cursor: pointer;
            color: blue;
            text-decoration: underline;
        }
        .selected {
            background-color: yellow;
        }
    </style>
</head>
<body>
    <h1>База знаний</h1>
    <div class="container">
        <div class="column">
            <h2>Темы</h2>
            <table>
                <?php foreach ($knowledgeBase->getThemes() as $theme): ?>
                    <tr>
                        <td class="clickable <?= $knowledgeBase->isCurrentTheme($theme) ? 'selected' : '' ?>"
                            onclick="window.location.href='?theme=<?= urlencode($theme) ?>&subtheme=<?= urlencode(array_key_first($knowledgeBase->getSubthemes($theme))) ?>'">
                            <?= htmlspecialchars($theme) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="column">
            <h2>Подтемы</h2>
            <table>
                <?php foreach ($knowledgeBase->getSubthemes($knowledgeBase->getCurrentTheme()) as $subtheme): ?>
                    <tr>
                        <td class="clickable <?= $knowledgeBase->isCurrentSubtheme($subtheme) ? 'selected' : '' ?>"
                            onclick="window.location.href='?theme=<?= urlencode($knowledgeBase->getCurrentTheme()) ?>&subtheme=<?= urlencode($subtheme) ?>'">
							<?= htmlspecialchars($subtheme) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="column">
            <h2>Содержимое</h2>
            <p><?= htmlspecialchars($knowledgeBase->getContent($knowledgeBase->getCurrentTheme(), $knowledgeBase->getCurrentSubtheme())) ?></p>
        </div>
    </div>
</body>
</html>
