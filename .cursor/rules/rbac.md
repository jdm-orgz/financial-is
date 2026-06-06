# Role-Based Access Control (RBAC) Rules

This project uses Casbin (`casbin/laravel-authz`) for role-based access permission.

## Roles
1. `super admin`
2. `admin`
3. `supervisor`
4. `spg`

## Permissions
1. **menu access**: Determines if a role can view/access a specific menu section.
2. **crud**: Determines if a role can Create, Read, Update, and Delete resources within that menu.

## Rules per Role

### `super admin`
- **Menu Access**: All menus (`*`)
- **CRUD**: All actions (`*`)

### `admin`
- **Menu Access**: Menu master and configuration (`master/*`, `configuration/*`)
- **CRUD**: All actions for master and configuration (`*`)

### `supervisor`
- **Menu Access**: Menu transaction - approval (`transaction/approval/*`)
- **CRUD**: All actions for transaction - approval (`*`)

### `spg`
- **Menu Access**: Menu transaction (`transaction/*`)
- **CRUD**: All actions for transaction (`*`)

## Implementation Details
- `super admin` is granted access via Casbin Matcher or Policy definition to bypass all checks.
- When generating new routes or features, ensure Casbin Enforcement aligns with these rules!
