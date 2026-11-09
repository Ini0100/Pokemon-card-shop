-- ---------------------------------------------------------------------------
-- TCG Market — demo data
-- Run after schema.sql. Trading cards are not included here: import them with
--   php bin/import-cards.php
--
-- Demo accounts:
--   admin@tcgmarket.test / admin1234  (admin)
--   demo@tcgmarket.test  / demo1234   (customer)
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

INSERT INTO users (id, email, password_hash, role, is_active) VALUES
(1, 'admin@tcgmarket.test', '$2y$10$LuAy2Xf/Qy/nuTI5moh3juJd3/6MflQSI6iEUWlFENODtsmuc2tW6', 'admin', 1),
(2, 'demo@tcgmarket.test',  '$2y$10$Ep5AGIzrKdfC0EMycXXpyuvRGELwB.miHJVg0B3tHeQi0mLURwiKW', 'customer', 1);

INSERT INTO categories (id, slug, name) VALUES
(1, 'boosters', 'Sobres'),
(2, 'cards',    'Cartas'),
(3, 'dice',     'Dados'),
(4, 'boxes',    'Cajas'),
(5, 'sleeves',  'Fundas');

-- Property and value codes are referenced by the card importer and the
-- booster opening logic; labels are what the shop displays.
INSERT INTO properties (id, code, name) VALUES
(1, 'card_kind',   'Tipo de carta'),
(2, 'rarity',      'Rareza'),
(3, 'element',     'Tipo'),
(4, 'collection',  'Expansión'),
(5, 'faces',       'Número de caras'),
(6, 'box_type',    'Tipo de caja'),
(7, 'sleeve_type', 'Tipo de funda');

INSERT INTO property_values (id, property_id, code, label) VALUES
(1,  1, 'pokemon',       'Pokémon'),
(2,  1, 'trainer',       'Entrenador'),
(3,  1, 'energy',        'Energía'),
(4,  2, 'common',        'Común'),
(5,  2, 'uncommon',      'Poco común'),
(6,  2, 'rare',          'Rara'),
(7,  2, 'rare_holo',     'Rara holo'),
(8,  3, 'grass',         'Planta'),
(9,  3, 'fire',          'Fuego'),
(10, 3, 'water',         'Agua'),
(11, 3, 'lightning',     'Eléctrico'),
(12, 3, 'psychic',       'Psíquico'),
(13, 3, 'fighting',      'Lucha'),
(14, 3, 'colorless',     'Incoloro'),
(15, 4, 'base1',         'Edición Básica'),
(16, 4, 'base2',         'Jungla'),
(17, 4, 'base3',         'Fósil'),
(18, 5, 'd4',            '4 caras'),
(19, 5, 'd6',            '6 caras'),
(20, 5, 'd20',           '20 caras'),
(21, 5, 'd100',          '100 caras'),
(22, 6, 'basic',         'Básica'),
(23, 6, 'premium',       'Premium'),
(24, 6, 'ultra_premium', 'Ultra Premium'),
(25, 7, 'basic',         'Básica'),
(26, 7, 'perfect_fit',   'Perfect Fit');

INSERT INTO category_properties (category_id, property_id) VALUES
(1, 4),                  -- Boosters: expansion
(2, 1), (2, 2), (2, 3), (2, 4), -- Cards: kind, rarity, element, expansion
(3, 3), (3, 5),          -- Dice: element, faces
(4, 4), (4, 6),          -- Boxes: expansion, box type
(5, 7);                  -- Sleeves: sleeve type

INSERT INTO products (id, name, description, price, shipping_cost, stock, image_path) VALUES
-- Boosters: opening one grants cards of its expansion (see AlbumService)
(1, 'Sobre Edición Básica', 'Sobre de la expansión Edición Básica (1999). Contiene 1 carta rara, 1 poco común y 1 común.', 4.99, 1.00, 25, 'assets/img/products/boosters/booster-base1.svg'),
(2, 'Sobre Jungla',         'Sobre de la expansión Jungla (1999). Contiene 1 carta rara, 1 poco común y 1 común.',         4.99, 1.00, 25, 'assets/img/products/boosters/booster-base2.svg'),
(3, 'Sobre Fósil',          'Sobre de la expansión Fósil (1999). Contiene 1 carta rara, 1 poco común y 1 común.',          4.99, 1.00, 25, 'assets/img/products/boosters/booster-base3.svg'),
-- Dice
(4, 'Dado de 6 caras · Eléctrico', 'Dado de seis caras en amarillo eléctrico con un rayo en la cara del seis. Ideal como contador de daño.', 2.99, 0.50,  9, 'assets/img/products/dice/d6-electric.svg'),
(5, 'Dado de 20 caras · Agua',     'Dado de veinte caras en tonos azules con acabado facetado. Perfecto para llevar la cuenta de los PS.', 4.99, 0.75, 10, 'assets/img/products/dice/d20-water.svg'),
-- Boxes
(6, 'Caja básica',        'Caja rígida con tapa para guardar un mazo de hasta 75 cartas enfundadas.',                    14.99, 3.00, 10, 'assets/img/products/boxes/basic-box.svg'),
(7, 'Caja Ultra Premium', 'Caja de coleccionista con acabado metalizado y cinta dorada para guardar tus cartas más valiosas.', 39.99, 5.00,  9, 'assets/img/products/boxes/ultra-premium-box.svg'),
-- Sleeves
(8, 'Fundas básicas',     'Paquete de 60 fundas protectoras estándar de tamaño oficial.',              1.99, 0.50, 10, 'assets/img/products/sleeves/basic-sleeves.svg'),
(9, 'Fundas Perfect Fit', 'Fundas interiores ajustadas y transparentes para tus cartas de más valor.', 3.49, 0.75,  9, 'assets/img/products/sleeves/perfect-fit-sleeves.svg');

INSERT INTO product_categories (product_id, category_id) VALUES
(1, 1), (2, 1), (3, 1),
(4, 3), (5, 3),
(6, 4), (7, 4),
(8, 5), (9, 5);

INSERT INTO product_property_values (product_id, property_value_id) VALUES
(1, 15), (2, 16), (3, 17),  -- Boosters → expansion
(4, 19), (4, 11),           -- d6, electric
(5, 20), (5, 10),           -- d20, water
(6, 22), (6, 15),           -- basic box, Base Set
(7, 24), (7, 16),           -- ultra premium box, Jungle
(8, 25),
(9, 26);
