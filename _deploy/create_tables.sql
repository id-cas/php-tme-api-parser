
CREATE TABLE IF NOT EXISTS `db_database_name`.`tme_products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `symbol` VARCHAR(256) NOT NULL,
  `obj_id` INT UNSIGNED DEFAULT NULL,
  `create_time` INT NOT NULL DEFAULT (UNIX_TIMESTAMP()),
  INDEX `fk_tme_products_cms3_objects_idx` (`obj_id` ASC) VISIBLE,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
  UNIQUE INDEX `symbol_UNIQUE` (`symbol` ASC) VISIBLE,

  CONSTRAINT `FK_Content table relation with cms3_objects`
    FOREIGN KEY (`obj_id`)
    REFERENCES `db_database_name`.`cms3_objects` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE

  )
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `db_database_name`.`tme_parameters` (
  `id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(256) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE)
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `db_database_name`.`tme_parameter_values` (
  `id` INT UNSIGNED NOT NULL,
  `value` VARCHAR(256) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE)
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `db_database_name`.`tme_parameter_value_separator` (
  `id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE)
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `db_database_name`.`tme_product_parameters` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `param_id` INT UNSIGNED NOT NULL,
  `value_id` INT UNSIGNED NOT NULL,
  `value_sep_id` INT UNSIGNED DEFAULT NULL,
  INDEX `fk_tme_product_parameters_tme_products_idx` (`product_id` ASC) VISIBLE,
  INDEX `fk_tme_product_parameters_tme_parameters_idx` (`param_id` ASC) VISIBLE,
  INDEX `fk_tme_product_parameters_tme_parameter_values_idx` (`value_id` ASC) VISIBLE,
  INDEX `fk_tme_product_parameters_tme_parameter_value_separator_idx` (`value_sep_id` ASC) VISIBLE,
  UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
  PRIMARY KEY (`id`),

  CONSTRAINT `FK_Content table relation with tme_products`
    FOREIGN KEY (`product_id`)
    REFERENCES `db_database_name`.`tme_products` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT `FK_Content table relation with tme_parameters`
    FOREIGN KEY (`param_id`)
    REFERENCES `db_database_name`.`tme_parameters` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT `FK_Content table relation with tme_parameter_values`
    FOREIGN KEY (`value_id`)
    REFERENCES `db_database_name`.`tme_parameter_values` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT `FK_Content table relation with tme_parameter_value_separator`
    FOREIGN KEY (`value_sep_id`)
    REFERENCES `db_database_name`.`tme_parameter_value_separator` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `db_database_name`.`tme_product_parameters_date` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `actual_date` INT NOT NULL,
  INDEX `fk_tme_product_parameters_date_tme_products_idx` (`product_id` ASC) VISIBLE,
  UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
  PRIMARY KEY (`id`),

  CONSTRAINT `FK_Content parameters_date relation with tme_products`
    FOREIGN KEY (`product_id`)
    REFERENCES `db_database_name`.`tme_products` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;



CREATE TABLE IF NOT EXISTS `db_database_name`.`tme_product_prices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `amount` INT UNSIGNED NOT NULL,
  `price_value` DOUBLE NOT NULL,
  `special` INT UNSIGNED DEFAULT NULL,
  `update_time` INT NOT NULL DEFAULT (UNIX_TIMESTAMP()),
  INDEX `fk_tme_product_prices_tme_products_idx` (`product_id` ASC) VISIBLE,

  UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
  PRIMARY KEY (`id`),

  CONSTRAINT `FK_Content tme_product_prices relation with tme_products`
    FOREIGN KEY (`product_id`)
    REFERENCES `db_database_name`.`tme_products` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


CREATE TABLE IF NOT EXISTS `db_database_name`.`tme_product_stocks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `unit` VARCHAR(8) NOT NULL,
  `amount` INT UNSIGNED NOT NULL,
  `update_time` INT NOT NULL DEFAULT (UNIX_TIMESTAMP()),
  INDEX `fk_tme_product_stock_tme_products_idx` (`product_id` ASC) VISIBLE,

  UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
  PRIMARY KEY (`id`),

  CONSTRAINT `tme_product_stocks table relation with tme_products`
    FOREIGN KEY (`product_id`)
    REFERENCES `db_database_name`.`tme_products` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;
