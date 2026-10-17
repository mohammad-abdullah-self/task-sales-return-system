# Laravel Project Setup Guide

This document explains how to run the project locally.

## Requirements

Make sure the following software versions are installed on your system:

- PHP: **8.4**
- Composer: **2.10**
- Bun: **1.4**
- Docker: **29.8**

## Installation Steps

Follow these steps to run the project:

### 1. Install PHP Dependencies

```bash
git clone https://github.com/mohammad-abdullah-self/task-sales-return-system.git
cd task-sales-return-system
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed
bun install
bun run dev
```
