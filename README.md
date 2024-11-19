В данном репозитории представлен пример кода, который я пишу.

Конкретно — реализован метод API `/print-service/compare-order-short`, который формирует excel-файл через библиотеку Phpspreadsheet сравнительной таблицы заявок на тендер

cUrl для примера: 
```
curl --location 'http://localhost:8000/print-service/compare-order-short' \
--header 'Cookie: PHPSESSID=sug3ihm4704ei483bc9h6jud4p; _csrf=9f1032007f59fc338c6df1285594e35e2c9db0abf3ad878e681308de75697f88a%3A2%3A%7Bi%3A0%3Bs%3A5%3A%22_csrf%22%3Bi%3A1%3Bs%3A32%3A%22R1U43SHKvrRue_V6glE4jpvtPG8UXOwE%22%3B%7D' \
--form 'tender_id="213"' \
--form 'orders[]="145"' \
--form 'orders[]="143"'
```

Непосредственно пример кода, который я пишу, находится в файле [basic/models/printService/handlers/CompareOrderShortHandler.php](https://github.com/CaptainSavage/izvekov-code-example/blob/c3cbf58d0b651b517261b8b1921981d8b9fdbdb3/basic/models/printService/handlers/CompareOrderShortHandler.php). Остальное — "обвязка" для работы этого кода (модели, с которыми он взаимодействует, контроллер и проч). 

Вкратце, как это работает: 
  1. Валидируем пришедшие данные с помощью `basic/models/printService/requests/CompareOrderShortRequest.php`
  2. Если всё ок, собираем данные из БД в [CompareOrderShortHandler::prepareData](https://github.com/CaptainSavage/izvekov-code-example/blob/c3cbf58d0b651b517261b8b1921981d8b9fdbdb3/basic/models/printService/handlers/CompareOrderShortHandler.php#L44)
  3. Анализируем, есть ли лоты в тендере, исходя из этого будем чуть по-разному создавать excel
  4. Читаем файл-шаблон `basic/templates/compare-order-short.xlsx`, из него формируем excel, из него же копируем стили на другие ячейки, если требуется (чтобы вручную их не набивать)
  5. Отправляем в ответе excel-файл

Результат работы этого кода расположил на Яндекс Диске — https://disk.yandex.ru/i/8YVwWgmMAoweqw 
