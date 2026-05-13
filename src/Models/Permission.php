<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use LaraArabDev\FilamentGatekeeper\Enums\PermissionType;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property string|null $type
 * @property string|null $entity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Permission extends SpatiePermission
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \LaraArabDev\FilamentGatekeeper\Database\Factories\PermissionFactory
    {
        return \LaraArabDev\FilamentGatekeeper\Database\Factories\PermissionFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'guard_name',
        'type',
        'entity',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => 'string',
    ];

    /**
     * Permission type constants (string values for DB/config). Use PermissionType enum for type-safe checks.
     */
    public const TYPE_RESOURCE = 'resource';
    public const TYPE_MODEL = 'model';
    public const TYPE_PAGE = 'page';
    public const TYPE_WIDGET = 'widget';
    public const TYPE_FIELD = 'field';
    public const TYPE_COLUMN = 'column';
    public const TYPE_ACTION = 'action';
    public const TYPE_RELATION = 'relation';

    /**
     * Get the type as an enum instance, or null if type is invalid/missing.
     */
    public function getTypeEnum(): ?PermissionType
    {
        return $this->type !== null && $this->type !== ''
            ? PermissionType::tryFrom($this->type)
            : null;
    }

    /**
     * Get all permission types (for selects). Config can override labels.
     *
     * @return array<string, string>
     */
    public static function getTypes(): array
    {
        return config('gatekeeper.types', PermissionType::optionsForSelect());
    }

    /**
     * Scope a query to only include permissions of a specific type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include resource permissions.
     */
    public function scopeResources(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_RESOURCE);
    }

    /**
     * Scope a query to only include model permissions (API models without resources).
     */
    public function scopeModels(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_MODEL);
    }

    /**
     * Scope a query to only include page permissions.
     */
    public function scopePages(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_PAGE);
    }

    /**
     * Scope a query to only include widget permissions.
     */
    public function scopeWidgets(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_WIDGET);
    }

    /**
     * Scope a query to only include field permissions.
     */
    public function scopeFields(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_FIELD);
    }

    /**
     * Scope a query to only include column permissions.
     */
    public function scopeColumns(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_COLUMN);
    }

    /**
     * Scope a query to only include action permissions.
     */
    public function scopeActions(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_ACTION);
    }

    /**
     * Scope a query to only include relation permissions.
     */
    public function scopeRelations(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_RELATION);
    }

    /**
     * Scope a query to only include permissions for a specific guard.
     */
    public function scopeForGuard(Builder $query, string $guard): Builder
    {
        return $query->where('guard_name', $guard);
    }

    /**
     * Scope a query to only include permissions matching a pattern.
     */
    public function scopeMatching(Builder $query, string $pattern): Builder
    {
        return $query->where('name', 'like', $pattern);
    }

    /**
     * Scope a query to only include permissions for a specific model.
     */
    public function scopeForModel(Builder $query, string $model): Builder
    {
        $modelSnake = str($model)->snake()->toString();

        return $query->where('name', 'like', "%_{$modelSnake}")
            ->orWhere('name', 'like', "%_{$modelSnake}_%");
    }

    /**
     * Scope a query to only include permissions for a specific entity.
     */
    public function scopeForEntity(Builder $query, string $entity): Builder
    {
        return $query->where('entity', $entity);
    }

    /**
     * Get the grouping key for this permission (by entity).
     * Uses entity column when set, otherwise derives from name. Always returns snake_case lowercase.
     * @return string
     */
    public function getEntityGroupKey(): string
    {
        $key = $this->entity ?? $this->getModelName();
        if ($key === null || $key === '') {
            return 'other';
        }
        return str($key)->snake()->lower()->toString();
    }

    /**
     * Get the model name from the permission name.
     * Uses entity when set (synced from PermissionRegistrar), otherwise parses from name.
     * Returns studly case (e.g. User, BlogPost) for display/backward compatibility.
     */
    public function getModelName(): ?string
    {
        if (isset($this->entity) && $this->entity !== '') {
            return str($this->entity)->studly()->toString();
        }

        $parts = explode('_', $this->name);

        if (count($parts) < 2) {
            return null;
        }

        // For field/relation/column/action types, name format is {action}_{entity}_{type}
        // e.g. view_user_email_field → entity user_email, view_user_posts_relation → user_posts
        if (in_array($this->type, [self::TYPE_FIELD, self::TYPE_RELATION, self::TYPE_COLUMN, self::TYPE_ACTION], true)) {
            $between = array_slice($parts, 1, -1);

            if ($between === []) {
                return null;
            }
            // Entity column stores model name (e.g. user); when set use it for display
            if (isset($this->entity) && $this->entity !== '') {
                return str($this->entity)->studly()->toString();
            }
            // Parse from name: first segment after action is model (e.g. view_user_email_field → User)
            return str((string) $between[0])->studly()->toString();
        }

        // For resource permissions like "view_any_user" or "view_any_blog_post"
        // Skip action prefixes (view, create, update, delete, etc.) and "any"
        $skipWords = ['view', 'create', 'update', 'delete', 'restore', 'force', 'replicate', 'reorder', 'any'];

        // Find the model name parts (everything after action prefixes)
        $modelParts = [];
        $foundAction = false;

        foreach ($parts as $part) {
            if (in_array($part, $skipWords)) {
                $foundAction = true;
                continue;
            }

            // Once we've found an action, collect the remaining parts as model name
            if ($foundAction || !in_array($part, $skipWords)) {
                $modelParts[] = $part;
            }
        }

        if (empty($modelParts)) {
            // Fallback: use the last part
            $modelParts = [end($parts)];
        }

        // Convert to PascalCase (e.g., ['blog', 'post'] -> 'BlogPost')
        return implode('', array_map('ucfirst', $modelParts));
    }

    /**
     * Get the entity name based on permission type.
     * Returns appropriate name for widgets, pages, resources, etc.
     */
    public function getEntityName(): ?string
    {
        $name = $this->name;
        $type = $this->type;

        return match ($type) {
            self::TYPE_PAGE => $this->extractPageName($name),
            self::TYPE_WIDGET => $this->extractWidgetName($name),
            self::TYPE_FIELD => $this->extractFieldName($name),
            self::TYPE_COLUMN => $this->extractColumnName($name),
            self::TYPE_ACTION => $this->extractActionEntityName($name),
            self::TYPE_RELATION => $this->extractRelationName($name),
            default => $this->getModelName(),
        };
    }

    /**
     * Extract page name from permission (e.g., "view_dashboard_page" → "Dashboard")
     */
    protected function extractPageName(string $name): ?string
    {
        // Pattern: view_dashboard_page or view_page_dashboard or access_page_settings
        if (preg_match('/_page_(.+)$/', $name, $matches)) {
            return str($matches[1])->headline()->toString();
        }

        // Pattern: view_dashboard_page (page at the end)
        if (preg_match('/^(.+)_page$/', $name, $matches)) {
            // Extract the page name (remove action prefix like "view_")
            $pagePart = $matches[1];
            if (preg_match('/^view_(.+)$/', $pagePart, $pageMatches)) {
                return str($pageMatches[1])->headline()->toString();
            }
            return str($pagePart)->headline()->toString();
        }

        return $this->getModelName();
    }

    /**
     * Extract widget name from permission (e.g., "view_stats_overview_widget" → "Stats Overview")
     */
    protected function extractWidgetName(string $name): ?string
    {
        // Pattern: view_widget_stats_overview or view_stats_overview_widget
        if (preg_match('/_widget_(.+)$/', $name, $matches)) {
            return str($matches[1])->headline()->toString();
        }

        // Pattern: view_stats_overview_widget (widget at the end)
        if (preg_match('/^(.+)_widget$/', $name, $matches)) {
            // Extract the widget name (remove action prefix like "view_")
            $widgetPart = $matches[1];
            if (preg_match('/^view_(.+)$/', $widgetPart, $widgetMatches)) {
                return str($widgetMatches[1])->headline()->toString();
            }
            return str($widgetPart)->headline()->toString();
        }

        return $this->getModelName();
    }

    /**
     * Extract field name from permission (e.g. "view_user_email_field" → "User Email (Field)")
     */
    protected function extractFieldName(string $name): ?string
    {
        // New format: action_entity_field (e.g. view_user_email_field)
        if (preg_match('/^(.+)_field$/', $name, $matches)) {
            return str($matches[1])->replace('_', ' ')->headline()->toString() . ' (Field)';
        }

        return $this->getModelName();
    }

    /**
     * Extract column name from permission (e.g. "view_user_email_column" → "User Email (Column)")
     */
    protected function extractColumnName(string $name): ?string
    {
        // New format: action_entity_column (e.g. view_user_email_column)
        if (preg_match('/^(.+)_column$/', $name, $matches)) {
            return str($matches[1])->replace('_', ' ')->headline()->toString() . ' (Column)';
        }

        return $this->getModelName();
    }

    /**
     * Extract action entity name from permission (e.g. "execute_user_export_action" → "User Export (Action)")
     */
    protected function extractActionEntityName(string $name): ?string
    {
        // New format: action_entity_action (e.g. execute_product_publish_action)
        if (preg_match('/^(.+)_action$/', $name, $matches)) {
            return str($matches[1])->replace('_', ' ')->headline()->toString() . ' (Action)';
        }

        return $this->getModelName();
    }

    /**
     * Extract relation name from permission (e.g. "view_user_posts_relation" → "User Posts (Relation)")
     */
    protected function extractRelationName(string $name): ?string
    {
        // New format: action_entity_relation (e.g. view_user_posts_relation)
        if (preg_match('/^(.+)_relation$/', $name, $matches)) {
            return str($matches[1])->replace('_', ' ')->headline()->toString() . ' (Relation)';
        }

        return $this->getModelName();
    }

    /**
     * Get the action from the permission name.
     */
    public function getAction(): ?string
    {
        $parts = explode('_', $this->name);

        if (count($parts) < 2) {
            return null;
        }

        // Remove the last part (model name)
        array_pop($parts);

        return implode('_', $parts);
    }

    /**
     * Get the action part formatted for display (e.g. "View Any", "Update User Email Field").
     */
    public function getActionLabel(): string
    {
        // For field/column/relation/action use Action + Entity + Type (e.g. "Execute Product Publish Action")
        if (in_array($this->type, [self::TYPE_FIELD, self::TYPE_COLUMN, self::TYPE_RELATION, self::TYPE_ACTION], true)) {
            return str($this->name)->replace('_', ' ')->headline()->toString();
        }

        $name = $this->name;
        $entityKey = $this->getEntityGroupKey();
        if ($entityKey !== 'other') {
            $name = preg_replace('/_' . preg_quote($entityKey, '/') . '$/', '', $name);
        }
        $name = preg_replace('/^(page_|widget_|field_|column_|action_|relation_)/', '', $name);

        return $name !== '' ? str($name)->headline()->toString() : str($this->name)->headline()->toString();
    }

    /**
     * Get entity display name for lists/badges. Uses entity column when set, else getEntityName().
     * @return string|null
     */
    public function getEntityDisplayName(): ?string
    {
        if (isset($this->entity) && $this->entity !== '') {
            return str($this->entity)->headline()->toString();
        }
        return $this->getEntityName() ?? 'Other';
    }

    /**
     * Get distinct entity options for filter dropdowns. Entity column first, then legacy from names.
     *
     * @return array<string, string>
     */
    public static function getDistinctEntityOptionsForFilter(): array
    {
        $fromColumn = static::query()
            ->whereNotNull('entity')
            ->where('entity', '!=', '')
            ->distinct()
            ->orderBy('entity')
            ->pluck('entity')
            ->mapWithKeys(fn(string $e) => [$e => str($e)->headline()->toString()])
            ->toArray();

        $fromName = static::query()
            ->get()
            ->map(fn(self $p) => $p->getEntityName())
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->mapWithKeys(fn($name) => [strtolower(str_replace(' ', '_', (string) $name)) => (string) $name])
            ->toArray();

        return array_merge($fromColumn, $fromName);
    }

    /**
     * Check if this is a resource permission.
     */
    public function isResourcePermission(): bool
    {
        return $this->getTypeEnum() === PermissionType::Resource;
    }

    /**
     * Check if this is a page permission.
     */
    public function isPagePermission(): bool
    {
        return $this->getTypeEnum() === PermissionType::Page;
    }

    /**
     * Check if this is a widget permission.
     */
    public function isWidgetPermission(): bool
    {
        return $this->getTypeEnum() === PermissionType::Widget;
    }

    /**
     * Check if this is a field permission.
     */
    public function isFieldPermission(): bool
    {
        return $this->getTypeEnum() === PermissionType::Field;
    }

    /**
     * Check if this is a column permission.
     */
    public function isColumnPermission(): bool
    {
        return $this->getTypeEnum() === PermissionType::Column;
    }

    /**
     * Check if this is an action permission.
     */
    public function isActionPermission(): bool
    {
        return $this->getTypeEnum() === PermissionType::Action;
    }

    /**
     * Check if this is a relation permission.
     */
    public function isRelationPermission(): bool
    {
        return $this->getTypeEnum() === PermissionType::Relation;
    }

    /**
     * Check if this is a model permission.
     */
    public function isModelPermission(): bool
    {
        return $this->getTypeEnum() === PermissionType::Model;
    }

    /**
     * Get the icon for the permission type.
     */
    public function getTypeIcon(): string
    {
        return $this->getTypeEnum()?->getIcon() ?? 'heroicon-o-shield-check';
    }

    /**
     * Get the color for the permission type.
     */
    public function getTypeColor(): string
    {
        return $this->getTypeEnum()?->getColor() ?? 'gray';
    }
}
