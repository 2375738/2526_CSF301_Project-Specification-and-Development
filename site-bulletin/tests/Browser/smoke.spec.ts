import { expect, test, type Page } from '@playwright/test';

async function loginWithPreset(page: Page, role: 'Employee' | 'Manager') {
  await page.goto('/login');
  await expect(page.getByRole('heading', { name: 'Quick Demo Access' })).toBeVisible();
  await page.getByRole('button', { name: new RegExp(`Enter as ${role}`, 'i') }).click();
  await expect(page).toHaveURL(/\/dashboard$/);
}

test('manager demo login exposes the full navigation shell', async ({ page }) => {
  await loginWithPreset(page, 'Manager');

  await expect(page.getByRole('link', { name: 'Announcements' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Messages' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Tasks', exact: true })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Governance', exact: true })).toBeVisible();
});

test('manager can search announcements and open the seeded update', async ({ page }) => {
  await loginWithPreset(page, 'Manager');

  await page.goto('/announcements');
  await page.getByRole('searchbox', { name: 'Search announcements' }).fill('Parking Rota');
  await page.getByRole('button', { name: 'Apply' }).click();

  await expect(page.getByRole('link', { name: 'Parking Rota Updated for This Week' })).toBeVisible();
  await page.getByRole('link', { name: 'Parking Rota Updated for This Week' }).click();
  await expect(page.getByRole('heading', { name: 'Parking Rota Updated for This Week' })).toBeVisible();
});

test('manager can search the inbox and open a seeded conversation', async ({ page }) => {
  await loginWithPreset(page, 'Manager');

  await page.goto('/messages');
  await page.getByRole('searchbox', { name: 'Search messages' }).fill('Follow-up on ticket queue');
  await page.getByRole('button', { name: 'Search' }).click();

  const conversationLink = page.getByRole('link', { name: 'Follow-up on ticket queue' }).first();
  await expect(conversationLink).toBeVisible();
  await conversationLink.click();
  await expect(page.getByRole('heading', { name: 'Follow-up on ticket queue' })).toBeVisible();
});

test('employee ticket list keeps the primary report issue cta visible', async ({ page }) => {
  await loginWithPreset(page, 'Employee');

  await page.goto('/tickets');
  await expect(page.getByRole('heading', { name: 'My Tickets' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Report issue', exact: true }).last()).toBeVisible();
});

test('employee can use knowledge search to find scanner guidance', async ({ page }) => {
  await loginWithPreset(page, 'Employee');

  await page.goto('/knowledge');
  await expect(page.getByRole('heading', { name: 'Knowledge Snippets' })).toBeVisible();
  await page.getByRole('searchbox', { name: 'Search knowledge snippets' }).fill('scanner');
  await page.getByRole('button', { name: 'Search' }).click();

  await expect(page.getByRole('heading', { name: 'Scanner reset steps' })).toBeVisible();
});

test('employee fast report preset preloads the issue form', async ({ page }) => {
  await loginWithPreset(page, 'Employee');

  await page.goto('/tickets/report?preset=scanner');
  await expect(page.getByRole('heading', { name: 'Report an Issue' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Fast Report' })).toBeVisible();
  await expect(page.locator('input[name="title"]')).toHaveValue('Scanner issue at station');
  await expect(page.locator('textarea[name="description"]')).toContainText('Scanner problem observed.');
});

test('employee can acknowledge an announcement as understood', async ({ page }) => {
  await loginWithPreset(page, 'Employee');

  await page.goto('/announcements');
  await page.getByRole('searchbox', { name: 'Search announcements' }).fill('Parking Rota');
  await page.getByRole('button', { name: 'Apply' }).click();
  await page.getByRole('link', { name: 'Parking Rota Updated for This Week' }).click();

  await expect(page.getByRole('heading', { name: 'Acknowledge This Update' })).toBeVisible();
  await page.getByRole('button', { name: 'Understood' }).click();
  await expect(page.getByText('Current acknowledgement:')).toBeVisible();
  await expect(page.getByText('Understood', { exact: true }).first()).toBeVisible();
});

test('manager dashboard shows the attention queue', async ({ page }) => {
  await loginWithPreset(page, 'Manager');

  await page.goto('/dashboard');
  await expect(page.getByRole('heading', { name: 'Attention Queue' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Breached Tickets' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Unread Conversations' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Pending Role Requests' })).toBeVisible();
});
