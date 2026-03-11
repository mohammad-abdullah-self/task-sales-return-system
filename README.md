# Laravel Project Setup Guide

This document explains how to run the project locally.

## Requirements

Make sure the following software versions are installed on your system:

- PHP: **8.4.1**
- Composer: **2.8.12**
- Bun: **1.3.10**
- Docker: **29.3.0**

## Installation Steps

Follow these steps to run the project:

### 1. Install PHP Dependencies

```bash
git clone https://github.com/mohammad-abdullah-self/task-sales-return-system.git
cd task-sales-return-system
composer install
./vendor/bin/sail up -d
bun install
bun run dev
```
