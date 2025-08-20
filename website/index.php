<?php
require 'db.php';
require 'crm_system.php';

$crmSystem = new CRMSystem($mysqli);

// Обработка POST запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $crmSystem->deleteItem($_POST['entity'], $_POST['item_id']);
        header("Location: index.php?entity=" . $_POST['entity']);
        exit;
    }
    
    if (isset($_POST['save'])) {
        $data = $_POST;
        $relatedIds = $data['related_ids'] ?? [];
        
        if (isset($data['item_id']) && $data['item_id']) {
            // Редактирование
            $crmSystem->updateItem($data['entity'], $data['item_id'], $data);
            $crmSystem->updateRelations($data['entity'], $data['item_id'], $relatedIds);
        } else {
            // Создание
            $crmSystem->createItem($data['entity'], $data);
            $newId = $mysqli->insert_id;
            $crmSystem->updateRelations($data['entity'], $newId, $relatedIds);
        }
        
        header("Location: index.php?entity=" . $data['entity'] . "&item_id=" . ($data['item_id'] ?? $newId));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Система - Управление сделками и контактами</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>CRM Система</h1>
            <p>Управление сделками и контактами</p>
        </header>

        <div class="content-wrapper">
            <!-- Меню -->
            <div class="column menu-column">
                <h2>Меню</h2>
                <div class="menu-list">
                    <?php foreach ($crmSystem->getEntities() as $entity => $name): ?>
                        <div class="menu-item <?= $crmSystem->isCurrentEntity($entity) ? 'selected' : '' ?>"
                             onclick="window.location.href='?entity=<?= $entity ?>'">
                            <?= htmlspecialchars($name) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="add-button">
                    <button onclick="showAddForm('<?= $crmSystem->getCurrentEntity() ?>')">
                        + Добавить <?= $crmSystem->getCurrentEntity() === 'deals' ? 'сделку' : 'контакт' ?>
                    </button>
                </div>
            </div>

            <!-- Список -->
            <div class="column list-column">
                <h2>Список</h2>
                <div class="items-list">
                    <?php foreach ($crmSystem->getItems($crmSystem->getCurrentEntity()) as $item): ?>
                        <div class="list-item <?= $item['id'] == $crmSystem->getCurrentItemId() ? 'selected' : '' ?>"
                             onclick="window.location.href='?entity=<?= $crmSystem->getCurrentEntity() ?>&item_id=<?= $item['id'] ?>'">
                            <?php if ($crmSystem->getCurrentEntity() === 'deals'): ?>
                                <?= htmlspecialchars($item['name']) ?>
                            <?php else: ?>
                                <?= htmlspecialchars($item['first_name'] . ' ' . $item['last_name']) ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Содержимое -->
            <div class="column content-column">
                <h2>Содержимое</h2>
                <?php if ($crmSystem->getCurrentItemId()): ?>
                    <?php 
                    $itemDetails = $crmSystem->getItemDetails($crmSystem->getCurrentEntity(), $crmSystem->getCurrentItemId());
                    $relatedItems = $crmSystem->getRelatedItems($crmSystem->getCurrentEntity(), $crmSystem->getCurrentItemId());
                    ?>
                    
                    <?php if ($itemDetails): ?>
                        <div class="item-details">
                            <form method="POST" class="details-form">
                                <input type="hidden" name="entity" value="<?= $crmSystem->getCurrentEntity() ?>">
                                <input type="hidden" name="item_id" value="<?= $itemDetails['id'] ?>">
                                
                                <table class="details-table">
                                    <?php if ($crmSystem->getCurrentEntity() === 'deals'): ?>
                                        <tr>
                                            <th>ID сделки:</th>
                                            <td><?= $itemDetails['id'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Наименование:</th>
                                            <td>
                                                <input type="text" name="name" value="<?= htmlspecialchars($itemDetails['name']) ?>" required>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Сумма:</th>
                                            <td>
                                                <input type="number" step="0.01" name="amount" value="<?= $itemDetails['amount'] ?>">
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <th>ID контакта:</th>
                                            <td><?= $itemDetails['id'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Имя:</th>
                                            <td>
                                                <input type="text" name="first_name" value="<?= htmlspecialchars($itemDetails['first_name']) ?>" required>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Фамилия:</th>
                                            <td>
                                                <input type="text" name="last_name" value="<?= htmlspecialchars($itemDetails['last_name']) ?>">
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </table>

                                <!-- Связанные элементы -->
                                <div class="related-items">
                                    <h3><?= $crmSystem->getCurrentEntity() === 'deals' ? 'Контакты:' : 'Сделки:' ?></h3>
                                    <div class="relation-selector">
                                        <?php 
                                        $allItems = $crmSystem->getAllItemsForRelation($crmSystem->getCurrentEntity());
                                        $currentRelatedIds = array_column($relatedItems, 'id');
                                        ?>
                                        <?php foreach ($allItems as $relItem): ?>
                                            <label>
                                                <input type="checkbox" name="related_ids[]" 
                                                       value="<?= $relItem['id'] ?>"
                                                       <?= in_array($relItem['id'], $currentRelatedIds) ? 'checked' : '' ?>>
                                                <?php if ($crmSystem->getCurrentEntity() === 'deals'): ?>
                                                    <?= htmlspecialchars($relItem['first_name'] . ' ' . $relItem['last_name']) ?>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($relItem['name']) ?>
                                                <?php endif; ?>
                                            </label><br>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="form-buttons">
                                    <button type="submit" name="save">Сохранить</button>
                                    <button type="button" onclick="if(confirm('Удалить этот элемент?')) { 
                                        document.querySelector('input[name=\'delete\']').click(); 
                                    }">Удалить</button>
                                    <input type="submit" name="delete" value="1" style="display:none">
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <p>Элемент не найден</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Выберите элемент из списка для просмотра и редактирования</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Модальное окно для добавления -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Добавить новый элемент</h3>
            <form method="POST" id="addForm">
                <input type="hidden" name="entity" id="modalEntity">
                <table class="details-table">
                    <tbody id="modalFields"></tbody>
                </table>
                <div class="form-buttons">
                    <button type="submit" name="save">Создать</button>
                    <button type="button" class="cancel-btn">Отмена</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddForm(entity) {
            document.getElementById('modalEntity').value = entity;
            const fieldsBody = document.getElementById('modalFields');
            fieldsBody.innerHTML = '';
            
            if (entity === 'deals') {
                fieldsBody.innerHTML = `
                    <tr>
                        <th>Наименование:</th>
                        <td><input type="text" name="name" required></td>
                    </tr>
                    <tr>
                        <th>Сумма:</th>
                        <td><input type="number" step="0.01" name="amount"></td>
                    </tr>
                `;
            } else {
                fieldsBody.innerHTML = `
                    <tr>
                        <th>Имя:</th>
                        <td><input type="text" name="first_name" required></td>
                    </tr>
                    <tr>
                        <th>Фамилия:</th>
                        <td><input type="text" name="last_name"></td>
                    </tr>
                `;
            }
            
            document.getElementById('addModal').style.display = 'block';
        }

        // Закрытие модального окна
        document.querySelector('.close').addEventListener('click', function() {
            document.getElementById('addModal').style.display = 'none';
        });

        document.querySelector('.cancel-btn').addEventListener('click', function() {
            document.getElementById('addModal').style.display = 'none';
        });

        window.addEventListener('click', function(event) {
            if (event.target === document.getElementById('addModal')) {
                document.getElementById('addModal').style.display = 'none';
            }
        });
    </script>
</body>
</html>