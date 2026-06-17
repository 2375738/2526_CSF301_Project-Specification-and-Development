import { expect, test, type Page } from '@playwright/test';

async function loginWithPreset(page: Page, role: 'Employee' | 'Manager') {
  await page.goto('/login');
  await expect(page.getByRole('heading', { name: 'Quick Demo Access' })).toBeVisible();
  await page.getByRole('button', { name: new RegExp(`Enter as ${role}`, 'i') }).click();
  await expect(page).toHaveURL(/\/dashboard$/);
}

async function logout(page: Page) {
  await page.getByRole('button', { name: 'Log Out' }).click();
  await expect(page).toHaveURL(/\/$/);
}

test('manager demo login exposes the full navigation shell', async ({ page }) => {
  await loginWithPreset(page, 'Manager');

  await expect(page.getByRole('link', { name: 'Messages' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Knowledge' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Tickets', exact: true })).toBeVisible();
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

  await page.goto('/tickets/report?template=scanner_issue');
  await expect(page.getByRole('heading', { name: 'Report an Issue' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Ticket Templates' })).toBeVisible();
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

test('employee can switch announcement acknowledgement from clarification to understood', async ({ page }) => {
  await loginWithPreset(page, 'Employee');

  await page.goto('/announcements');
  await page.getByRole('searchbox', { name: 'Search announcements' }).fill('Parking Rota');
  await page.getByRole('button', { name: 'Apply' }).click();
  await page.getByRole('link', { name: 'Parking Rota Updated for This Week' }).click();

  await expect(page.getByRole('heading', { name: 'Acknowledge This Update' })).toBeVisible();
  await page.getByRole('button', { name: 'Need Clarification' }).click();
  await expect(page.getByText('Clarification requested')).toBeVisible();

  await page.getByRole('button', { name: 'Understood' }).click();
  await expect(page.getByText('Clarification requested')).not.toBeVisible();
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

test('manager can find manager-only knowledge guidance', async ({ page }) => {
  await loginWithPreset(page, 'Manager');

  await page.goto('/knowledge');
  await page.getByRole('searchbox', { name: 'Search knowledge snippets' }).fill('escalation');
  await page.getByRole('button', { name: 'Search' }).click();

  await expect(page.getByRole('heading', { name: 'Manager-only escalation pack' })).toBeVisible();
});

test('employee does not see manager-only knowledge guidance in search results', async ({ page }) => {
  await loginWithPreset(page, 'Employee');

  await page.goto('/knowledge');
  await page.getByRole('searchbox', { name: 'Search knowledge snippets' }).fill('escalation');
  await page.getByRole('button', { name: 'Search' }).click();

  await expect(page.getByText('Manager-only escalation pack')).not.toBeVisible();
});

test('manager does not see or access the triage board', async ({ page }) => {
  await loginWithPreset(page, 'Manager');

  await page.goto('/tickets');
  await expect(page.getByRole('link', { name: 'Open triage board' })).not.toBeVisible();
  await expect(page.getByRole('link', { name: 'Approval workbench' })).toBeVisible();

  const response = await page.goto('/tickets/triage');
  expect(response?.status()).toBe(403);
});

test('manager can open the approval workbench', async ({ page }) => {
  await loginWithPreset(page, 'Manager');

  await page.goto('/tickets');
  await page.getByRole('link', { name: 'Approval workbench' }).click();

  await expect(page.getByRole('heading', { name: 'Approval Workbench' })).toBeVisible();
  await expect(page.getByText('Review request-style tickets that need manager or HR decisions')).toBeVisible();
});

test('employee can submit a shift swap request and manager can approve it from the workbench', async ({ page }) => {
  const ticketTitle = `Shift swap browser test ${Date.now()}`;

  await loginWithPreset(page, 'Employee');

  await page.goto('/tickets/report?template=shift_swap_request');
  await expect(page.getByRole('heading', { name: 'Report an Issue' })).toBeVisible();
  await page.locator('input[name="title"]').fill(ticketTitle);
  await page.locator('textarea[name="description"]').fill('Browser test shift swap request.');
  await page.locator('input[name="detail_answers[]"]').nth(0).fill('2026-03-14 10:00');
  await page.locator('input[name="detail_answers[]"]').nth(1).fill('2026-03-16 10:00');
  await page.locator('input[name="detail_answers[]"]').nth(2).fill('Browser Test Partner');
  await page.getByRole('button', { name: 'Submit Ticket' }).click();

  await expect(page.getByText(/Ticket #\d+ created\./)).toBeVisible();
  await expect(page.getByRole('heading', { name: ticketTitle })).toBeVisible();

  await logout(page);
  await loginWithPreset(page, 'Manager');

  await page.goto('/tickets/approvals');
  await expect(page.getByRole('heading', { name: 'Approval Workbench' })).toBeVisible();
  await page.getByRole('searchbox', { name: 'Search approvals' }).fill(ticketTitle);
  await page.getByRole('button', { name: 'Apply' }).click();

  const approvalCard = page.locator('article').filter({ hasText: ticketTitle }).first();
  await expect(approvalCard).toBeVisible();
  await approvalCard.getByRole('textbox', { name: 'Public note' }).fill('Approved from browser smoke test.');
  await approvalCard.getByRole('button', { name: 'Approve' }).click();

  await expect(page.getByText('Approval decision saved.')).toBeVisible();
  await expect(page.locator('article').filter({ hasText: ticketTitle }).first().getByText('Approved from browser smoke test.')).toBeVisible();
});
