<?php

namespace PiggyCustomEnchants;

use PiggyCustomEnchants\Blocks\PiggyObsidian;
use PiggyCustomEnchants\Commands\CustomEnchantCommand;
use PiggyCustomEnchants\CustomEnchants\CustomEnchants;
use PiggyCustomEnchants\CustomEnchants\CustomEnchantsIds;
use PiggyCustomEnchants\Entities\PiggyFireball;
use PiggyCustomEnchants\Entities\PiggyLightning;
use PiggyCustomEnchants\Entities\PigProjectile;
use PiggyCustomEnchants\Entities\VolleyArrow;
use PiggyCustomEnchants\Entities\PiggyWitherSkull;
use PiggyCustomEnchants\Tasks\AutoAimTask;
use PiggyCustomEnchants\Tasks\CactusTask;
use PiggyCustomEnchants\Tasks\ChickenTask;
use PiggyCustomEnchants\Tasks\EffectTask;
use PiggyCustomEnchants\Tasks\ForcefieldTask;
use PiggyCustomEnchants\Tasks\JetpackTask;
use PiggyCustomEnchants\Tasks\MeditationTask;
use PiggyCustomEnchants\Tasks\ParachuteTask;
use PiggyCustomEnchants\Tasks\PoisonousGasTask;
use PiggyCustomEnchants\Tasks\ProwlTask;
use PiggyCustomEnchants\Tasks\RadarTask;
use PiggyCustomEnchants\Tasks\SizeTask;
use PiggyCustomEnchants\Tasks\SpiderTask;
use PiggyCustomEnchants\Tasks\VacuumTask;
use pocketmine\block\BlockFactory;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\item\Armor;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Position;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

/**
 * Class Main
 * @package PiggyCustomEnchants
 */
class Main extends PluginBase
{
    const MAX_LEVEL = 0;
    const NOT_COMPATIBLE = 1;
    const NOT_COMPATIBLE_WITH_OTHER_ENCHANT = 2;
    const MORE_THAN_ONE = 3;

    const ROMAN_CONVERSION_TABLE = [
        'M' => 1000,
        'CM' => 900,
        'D' => 500,
        'CD' => 400,
        'C' => 100,
        'XC' => 90,
        'L' => 50,
        'XL' => 40,
        'X' => 10,
        'IX' => 9,
        'V' => 5,
        'IV' => 4,
        'I' => 1
    ];

    const COLOR_CONVERSION_TABLE = [
        "BLACK" => TextFormat::BLACK,
        "DARK_BLUE" => TextFormat::DARK_BLUE,
        "DARK_GREEN" => TextFormat::DARK_GREEN,
        "DARK_AQUA" => TextFormat::DARK_AQUA,
        "DARK_RED" => TextFormat::DARK_RED,
        "DARK_PURPLE" => TextFormat::DARK_PURPLE,
        "GOLD" => TextFormat::GOLD,
        "GRAY" => TextFormat::GRAY,
        "DARK_GRAY" => TextFormat::DARK_GRAY,
        "BLUE" => TextFormat::BLUE,
        "GREEN" => TextFormat::GREEN,
        "AQUA" => TextFormat::AQUA,
        "RED" => TextFormat::RED,
        "LIGHT_PURPLE" => TextFormat::LIGHT_PURPLE,
        "YELLOW" => TextFormat::YELLOW,
        "WHITE" => TextFormat::WHITE
    ];

    const PIGGY_ENTITIES = [
        PiggyFireball::class,
        PiggyLightning::class,
        PigProjectile::class,
        VolleyArrow::class,
        PiggyWitherSkull::class
    ];

    public $berserkercd;
    public $bountyhuntercd;
    public $cloakingcd;
    public $endershiftcd;
    public $growcd;
    public $implantscd;
    public $jetpackcd;
    public $shrinkcd;
    public $vampirecd;

    public $growremaining;
    public $jetpackDisabled;
    public $shrinkremaining;
    public $flyremaining;

    public $chickenTick;
    public $forcefieldParticleTick;
    public $gasParticleTick;
    public $jetpackChargeTick;
    public $meditationTick;

    public $blockface;

    public $glowing;
    public $grew;
    public $flying;
    public $hallucination;
    public $implants;
    public $mined;
    public $moved;
    public $nofall;
    public $overload;
    public $prowl;
    public $using;
    public $shrunk;

    public $formsEnabled = false;

    public static $lightningFlames = false;
    public static $blazeFlames = false;

    public $enchants = [
        //id => ["name", "slot", "trigger", "rarity", maxlevel", "description"]
        CustomEnchantsIds::ANTIKNOCKBACK => ["Anti Knockback", "Armor", "Damage", "Rare", 10, "Reduces knockback by 25% per armor piece"],
        CustomEnchantsIds::ANTITOXIN => ["Antitoxin", "Helmets", "Effect", "Mythic", 10, "Immunity to poison"],
        CustomEnchantsIds::AERIAL => ["Aerial", "Weapons", "Damage", "Common", 10, "Increases damage in air"],
        CustomEnchantsIds::ARMORED => ["Armored", "Armor", "Damage", "Rare", 10, "Decreases sword damage by 20l%"],
        CustomEnchantsIds::AUTOAIM => ["Auto Aim", "Bow", "Held", "Mythic", 10, "Aim at nearest target"],
        CustomEnchantsIds::AUTOREPAIR => ["Autorepair", "Damageable", "Move", "Uncommon", 10, "Automatically repairs items when moving"],
        CustomEnchantsIds::BACKSTAB => ["Backstab", "Weapons", "Damage", "Uncommon", 10, "When hitting from behind, you deal more damage."],
        CustomEnchantsIds::BERSERKER => ["Berserker", "Armor", "Damaged", "Rare", 10, "Gives strength on low health"],
        CustomEnchantsIds::BLESSED => ["Blessed", "Weapons", "Damage", "Uncommon", 10, "15l% (l = level) chance to remove bad effects"],
        CustomEnchantsIds::BLAZE => ["Blaze", "Bow", "Shoot", "Rare", 10, "Shoots fireballs"],
        CustomEnchantsIds::BLIND => ["Blind", "Weapons", "Damage", "Common", 10, "Gives enemies blindness"],
        CustomEnchantsIds::BOUNTYHUNTER => ["Bounty Hunter", "Bow", "Damage", "Uncommon", 10, "Collect bounties (items) when hitting enemies."],
        CustomEnchantsIds::CACTUS => ["Cactus", "Armor", "Equip", "Rare", 10, "Poke people around you", "Poke people around you"],
        CustomEnchantsIds::CHARGE => ["Charge", "Weapons", "Damage", "Uncommon", 10, "Increases damage when sprinting"],
        CustomEnchantsIds::CHICKEN => ["Chicken", "Chestplate", "Equip", "Uncommon", 10, "Lays egg every 5 minutes, 5l% (l = level) chance of rare drop"],
        CustomEnchantsIds::CLOAKING => ["Cloaking", "Armor", "Damaged", "Uncommon", 10, "Becomes invisible when hit"],
        CustomEnchantsIds::CRIPPLINGSTRIKE => ["Cripple", "Weapons", "Damage", "Common", 10, "Gives enemies nausea and slowness"],
        CustomEnchantsIds::CRIPPLE => ["Cripple", "Weapons", "Damage", "Common", 10, "Gives enemies nausea and slowness"],
        CustomEnchantsIds::CURSED => ["Cursed", "Armor", "Damaged", "Uncommon", 10, "Gives wither to enemy when hit"],
        CustomEnchantsIds::DEATHBRINGER => ["Deathbringer", "Weapons", "Damage", "Rare", 10, "Increases damage"],
        CustomEnchantsIds::DISARMING => ["Disarming", "Weapons", "Damage", "Uncommon", 10, "10l% chance to disarm enemy"],
        CustomEnchantsIds::DISARMOR => ["Disarmor", "Weapons", "Damage", "Uncommon", 10, "10l% chance to disarmor enemy"],
        CustomEnchantsIds::DRILLER => ["Driller", "Tools", "Break", "Uncommon", 10, "Breaks a 3 by 3 by 1 + level"],
        CustomEnchantsIds::DRUNK => ["Drunk", "Armor", "Damaged", "Rare", 10, "Gives slowness, mining fatigue, and nausea to enemy when hit"],
        CustomEnchantsIds::ENDERSHIFT => ["Endershift", "Armor", "Damaged", "Rare", 10, "Gives speed and extra health when low on health"],
        CustomEnchantsIds::ENERGIZING => ["Energizing", "Tools", "Break", "Uncommon", 10, "Gives haste when block is broken"],
        CustomEnchantsIds::ENLIGHTED => ["Enlighted", "Armor", "Damaged", "Uncommon", 10, "Gives regeneration when hit"],
        CustomEnchantsIds::ENRAGED => ["Enraged", "Chestplate", "Equip", "Rare", 10, "Strength per level"],
        CustomEnchantsIds::EXPLOSIVE => ["Explosive", "Tools", "Break", "Rare", 10, "Cause an explosion when block is broken"],
        CustomEnchantsIds::FARMER => ["Farmer", "Hoe", "Break", "Uncommon", 19, "Automatically regrows crops when harvested"],
        CustomEnchantsIds::FERTILIZER => ["Fertilizer", "Hoe", "Interact", "Uncommon", 10, "Creates farmland in a level radius around the block"],
        CustomEnchantsIds::FOCUSED => ["Focused", "Helmets", "Effect", "Uncommon", 10, "Nausea will affect you less"],
        CustomEnchantsIds::FORCEFIELD => ["Forcefield", "Armor", "Equip", "Mythic", 10, "Deflects projectiles and living entities in a 0.75x (x = # of armor pieces)"],
        CustomEnchantsIds::FROZEN => ["Frozen", "Armor", "Damaged", "Rare", 10, "Gives slowness to enemy when hit"],
        CustomEnchantsIds::GEARS => ["Gears", "Boots", "Equip", "Uncommon", 10, "Gives speed"],
        CustomEnchantsIds::GLOWING => ["Glowing", "Helmets", "Equip", "Common", 10, "Gives night vision"],
        CustomEnchantsIds::GOOEY => ["Gooey", "Weapons", "Damage", "Uncommon", 10, "Flings enemy into the air"],
        CustomEnchantsIds::GRAPPLING => ["Grappling", "Bow", "Projectile_Hit", "Rare", 10, "Pulls you to location of arrow. If enemy is hit, the enemy will be pulled to you."],
        CustomEnchantsIds::GROW => ["Grow", "Armor", "Sneak", "Uncommon", 10, "Increases size on sneak (Must be wearing full set of Grow armor)"],
        CustomEnchantsIds::HALLUCINATION => ["Hallucination", "Weapons", "Damage", "Mythic", 10, "5l% (l = level) chance of trapping enemies in a fake prison"],
        CustomEnchantsIds::HARDENED => ["Hardened", "Armor", "Damaged", "Uncommon", 10, "Gives weakness to enemy when hit"],
        CustomEnchantsIds::HASTE => ["Haste", "Tools", "Held", "Uncommon", 10, "Gives haste when held"],
        CustomEnchantsIds::HARVEST => ["Harvest", "Hoe", "Break", "Uncommon", 10, "Harvest crops in a level radius around the block"],
        CustomEnchantsIds::HEADHUNTER => ["Headhunter", "Bow", "Damage", "Uncommon", 10, "Increases damage if enemy is shot in the head"],
        CustomEnchantsIds::HEALING => ["Healing", "Bow", "Damage", "Rare", 10, "Heals target when shot"],
        CustomEnchantsIds::HEAVY => ["Heavy", "Armor", "Damage", "Rare", 10, "Decreases damage from axes by 20l%"],
        CustomEnchantsIds::IMPLANTS => ["Implants", "Helmets", "Move", "Rare", 10, "Replenishes hunger and air"],
        CustomEnchantsIds::JETPACK => ["Jetpack", "Boots", "Sneak", "Rare", 10, "Enable flying (you fly where you look) when you sneak."],
        CustomEnchantsIds::JACKPOT => ["Jackpot", "Tools", "Break", "Mythic", 10, "10l% chance to increase the ore tier"],
        CustomEnchantsIds::LIFESTEAL => ["Lifesteal", "Weapons", "Damage", "Common", 10, "Heals when damaging enemies"],
        CustomEnchantsIds::LIGHTNING => ["Lightning", "Weapons", "Damage", "Rare", 10, "10l% chance to strike enemies with lightning"],
        CustomEnchantsIds::LUMBERJACK => ["Lumberjack", "Axe", "Break", "Rare", 10, "Mines all logs connected to log when broken"],
        CustomEnchantsIds::MAGMAWALKER => ["Magma Walker", "Boots", "Move", "Uncommon", 10, "Turns lava into obsidian around you"],
        CustomEnchantsIds::MEDITATION => ["Meditation", "Helmets", "Equip", "Uncommon", 10, "Replenish health and hunger every 20 seconds (half a hunger bar/heart per level)"],
        CustomEnchantsIds::MISSILE => ["Missile", "Bow", "Projectile_Hit", "Rare", 10, "Spawns tnt on hit"],
        CustomEnchantsIds::MOLOTOV => ["Molotov", "Bow", "Projectile_Hit", "Uncommon", 10, "Starts fire around target"],
        CustomEnchantsIds::MOLTEN => ["Molten", "Armor", "Damaged", "Rare", 10, "Sets enemy on fire when hit"],
        CustomEnchantsIds::OBSIDIANSHIELD => ["Obsidian Shield", "Armor", "Equip", "Common", 10, "Gives fire resistance while worn"],
        CustomEnchantsIds::OVERLOAD => ["Overload", "Armor", "Equip", "Mythic", 100, "Gives 1 extra heart per level per armor piece"],
        CustomEnchantsIds::OXYGENATE => ["Oxygenate", "Tools", "Break", "Uncommon", 10, "Breathe underwater when held"],
        CustomEnchantsIds::PARACHUTE => ["Parachute", "Chestplate", "Equip", "Uncommon", 10, "Slows your fall (above 3 blocks)s"],
        CustomEnchantsIds::PARALYZE => ["Paralyze", "Bow", "Damage", "Rare", 10, "Gives slowness, blindness, and weakness"],
        CustomEnchantsIds::PIERCING => ["Piercing", "Bow", "Damage", "Rare", 10, "Ignores armor when dealing damage"],
        CustomEnchantsIds::POISON => ["Poison", "Weapons", "Damage", "Uncommon", 10, "Poisons enemies"],
        CustomEnchantsIds::POISONOUSCLOUD => ["Poisonous Cloud", "Armor", "Equip", "Rare", 10, "not sure"],
        CustomEnchantsIds::POISONED => ["Poisoned", "Armor", "Damaged", "Uncommon", 10, "Poisons enemy when hit"],
        CustomEnchantsIds::PORKIFIED => ["Porkified", "Bow", "Shoot", "Mythic", 3, "Shoot pigs"],
        CustomEnchantsIds::PROWL => ["Prowl", "Chestplate", "Equip", "Rare", 1, "Goes invisible when sneaking, gives slowness"],
        CustomEnchantsIds::QUICKENING => ["Quickening", "Tools", "Break", "Uncommon", 10, "Gives speed when block is broken"],
        CustomEnchantsIds::RADAR => ["Radar", "Compass", "Inventory", "Rare", 10, "Points to nearest player in a 50l (l = level) range."],
        CustomEnchantsIds::REVIVE => ["Revive", "Armor", "Death", "Rare", 10, "Will revive you when you die. (will lower/remove enchantment)"],
        CustomEnchantsIds::REVULSION => ["Revulsion", "Armor", "Damaged", "Uncommon", 5, "Gives nausea to enemy when hit"],
        CustomEnchantsIds::SELFDESTRUCT => ["Self Destruct", "Armor", "Damaged", "Rare", 5, "Spawn TNT when you die."],
        CustomEnchantsIds::SHIELDED => ["Shielded", "Armor", "Equip", "Rare", 10, "Gives resistance per level per piece of armor"],
        CustomEnchantsIds::SHRINK => ["Shrink", "Armor", "Sneak", "Uncommon", 2, "Decreases size on sneak (Must be wearing full set of Shrink armor)"],
        CustomEnchantsIds::SHUFFLE => ["Shuffle", "Bow", "Damage", "Rare", 1, "Switches position with target"],
        CustomEnchantsIds::SMELTING => ["Smelting", "Tools", "Break", "Uncommon", 10, "Automatically smelts drops when broken"],
        CustomEnchantsIds::SOULBOUND => ["Soulbound", "Global", "Death", "Mythic", 5, "Keeps item after death (will lower/remove enchantment)"],
        CustomEnchantsIds::SPIDER => ["Spider", "Chestplate", "Equip", "Rare", 1, "Climb walls"],
        CustomEnchantsIds::SPRINGS => ["Springs", "Boots", "Equip", "Uncommon", 5, "Gives a jump boost"],
        CustomEnchantsIds::STOMP => ["Stomp", "Boots", "Fall_Damage", "Uncommon", 5, "Deal part of fall damage to enemy when taking fall damage"],
        CustomEnchantsIds::TANK => ["Tank", "Armor", "Damage", "Uncommon", 5, "Spawn TNT when you die."],
        CustomEnchantsIds::TELEPATHY => ["Telepathy", "Tools", "Break", "Rare", 1, "Automatically puts drops in inventory."],
        CustomEnchantsIds::VACUUM => ["Vacuum", "Chestplate", "Equip", "Rare", 10, "Suck up items in a 3l radius"],
        CustomEnchantsIds::VAMPIRE => ["Vampire", "Weapons", "Damage", "Uncommon", 10, "Heals by part of damage dealt"],
        CustomEnchantsIds::VOLLEY => ["Volley", "Bow", "Shoot", "Uncommon", 10, "Shoot multiple arrows in a cone"],
        CustomEnchantsIds::WITHER => ["Wither", "Weapons", "Damage", "Uncommon", 5, "Gives enemies wither"],
        CustomEnchantsIds::WITHERSKULL => ["Wither Skull", "Bow", "Shoot", "Mythic", 1, "Shoots Wither Skull"],
        CustomEnchantsIds::PLACEHOLDER => ["Placeholder", "Bow", "Shoot", "Rare", 1, "idk"]
    ];

    public $incompatibilities = [
        CustomEnchantsIds::GROW => [CustomEnchantsIds::SHRINK],
        CustomEnchantsIds::PORKIFIED => [CustomEnchantsIds::BLAZE, CustomEnchantsIds::WITHERSKULL],
        CustomEnchantsIds::VOLLEY => [CustomEnchantsIds::GRAPPLING]
    ];

    public function onEnable()
    {
        if (!$this->isSpoon()) {
            $this->initCustomEnchants();
            $this->saveDefaultConfig();
            if ($this->getConfig()->getNested("forms.enabled")) {
                if ($this->getServer()->getPluginManager()->getPlugin("FormAPI") !== null) {
                    $this->formsEnabled = true;
                } else {
                    $this->getLogger()->error("Forms are enabled but FormAPI is not found.");
                }
            }
            if ($this->getConfig()->getNested("blaze.flames")) {
                self::$blazeFlames = true;
            }
            if ($this->getConfig()->getNested("lightning.flames")) {
                self::$lightningFlames = true;
            }
            $this->jetpackDisabled = $this->getConfig()->getNested("jetpack.disabled") ?? [];
            if (count($this->jetpackDisabled) > 0) {
                $this->getLogger()->info(TextFormat::RED . "Jetpack is currently disabled in the levels " . implode(", ", $this->jetpackDisabled) . ".");
            }
            BlockFactory::registerBlock(new PiggyObsidian(), true);
            foreach(self::PIGGY_ENTITIES as $piggyEntity) {
                Entity::registerEntity($piggyEntity, true);
            }

            if (!ItemFactory::isRegistered(Item::ENCHANTED_BOOK)) { //Check if it isn't already registered by another plugin
                ItemFactory::registerItem(new Item(Item::ENCHANTED_BOOK, 0, "Enchanted Book")); //This is a temporary fix for name being Unknown when given due to no implementation in PMMP. Will remove when implemented in PMMP
            }
            $this->getServer()->getCommandMap()->register("customenchant", new CustomEnchantCommand("customenchant", $this));
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new AutoAimTask($this), 1);
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new CactusTask($this), 10);
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new ChickenTask($this), 20);
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new ForcefieldTask($this), 1);
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new EffectTask($this), 5);
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new JetpackTask($this), 1);
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new MeditationTask($this), 20);
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new ParachuteTask($this), 2);
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new ProwlTask($this), 1);
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new RadarTask($this), 1);
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new SizeTask($this), 20);
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new SpiderTask($this), 1);
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new PoisonousGasTask($this), 1);
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new VacuumTask($this), 1);
            $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

            $this->getLogger()->info(TextFormat::GREEN . "Enabled.");
        }
    }

    /**
     * Checks if server is using a spoon.
     *
     * @return bool
     */
    public function isSpoon()
    {
        if ($this->getServer()->getName() !== "PocketMine-MP") {
            $this->getLogger()->error("Pig doesn't like spoons. Due to this, the plugin will not function until you are using PMMP.");
            return true;
        }
        if ($this->getDescription()->getAuthors() !== ["DaPigGuy"] || $this->getDescription()->getName() !== "PiggyCustomEnchants") {
            $this->getLogger()->error("You are not using the original version of this plugin (PiggyCustomEnchants) by DaPigGuy/MCPEPIG.");
            return true;
        }
        return false;
    }

    public function initCustomEnchants()
    {
        CustomEnchants::init();
        foreach ($this->enchants as $id => $data) {
            $ce = $this->translateDataToCE($id, $data);
            CustomEnchants::registerEnchantment($ce);
        }
    }

    /**
     * Registers enchantment from id, name, trigger, rarity, and max level
     *
     * @param $id
     * @param $name
     * @param $type
     * @param $trigger
     * @param $rarity
     * @param $maxlevel
     * @param $description
     */
    public function registerEnchantment($id, $name, $type, $trigger, $rarity, $maxlevel, $description = "")
    {
        $data = [$name, $type, $trigger, $rarity, $maxlevel, $description];
        $this->enchants[$id] = $data;
        $ce = $this->translateDataToCE($id, $data);
        CustomEnchants::registerEnchantment($ce);
    }

    /**
     * Unregisters enchantment by id
     *
     * @param $id
     * @return bool
     */
    public function unregisterEnchantment($id)
    {
        if (isset($this->enchants[$id]) && CustomEnchants::getEnchantment($id) !== null) {
            unset($this->enchants[$id]);
            CustomEnchants::unregisterEnchantment($id);
            return true;
        }
        return false;
    }

    /**
     * Add an enchant incompatibility
     *
     * @param int $id
     * @param array $incompatibilities
     * @return bool
     */
    public function addIncompatibility(int $id, array $incompatibilities)
    {
        if (!isset($this->incompatibilities[$id])) {
            $this->incompatibilities[$id] = $incompatibilities;
            return true;
        }
        return false;
    }

    /**
     * Translates data from strings to int
     *
     * @param $id
     * @param $data
     * @return CustomEnchants
     */
    public function translateDataToCE($id, $data)
    {
        $slot = CustomEnchants::SLOT_NONE;
        switch ($data[1]) {
            case "Global":
                $slot = CustomEnchants::SLOT_ALL;
                break;
            case "Weapons":
                $slot = CustomEnchants::SLOT_SWORD;
                break;
            case "Bow":
                $slot = CustomEnchants::SLOT_BOW;
                break;
            case "Tools":
                $slot = CustomEnchants::SLOT_TOOL;
                break;
            case "Pickaxe":
                $slot = CustomEnchants::SLOT_PICKAXE;
                break;
            case "Axe":
                $slot = CustomEnchants::SLOT_AXE;
                break;
            case "Shovel":
                $slot = CustomEnchants::SLOT_SHOVEL;
                break;
            case "Hoe":
                $slot = CustomEnchants::SLOT_HOE;
                break;
            case "Armor":
                $slot = CustomEnchants::SLOT_ARMOR;
                break;
            case "Helmets":
                $slot = CustomEnchants::SLOT_HEAD;
                break;
            case "Chestplate":
                $slot = CustomEnchants::SLOT_TORSO;
                break;
            case "Leggings":
                $slot = CustomEnchants::SLOT_LEGS;
                break;
            case "Boots":
                $slot = CustomEnchants::SLOT_FEET;
                break;
            case "Compass":
                $slot = 0b10000000000000;
                break;
        }
        $rarity = CustomEnchants::RARITY_COMMON;
        switch ($data[3]) {
            case "Common":
                $rarity = CustomEnchants::RARITY_COMMON;
                break;
            case "Uncommon":
                $rarity = CustomEnchants::RARITY_UNCOMMON;
                break;
            case "Rare":
                $rarity = CustomEnchants::RARITY_RARE;
                break;
            case "Mythic":
                $rarity = CustomEnchants::RARITY_MYTHIC;
                break;
        }
        $ce = new CustomEnchants($id, $data[0], $rarity, $slot, $data[4]);
        return $ce;
    }

    /**
     * Adds enchantment to item
     *
     * @param Item $item
     * @param $enchants
     * @param $levels
     * @param bool $check
     * @param CommandSender|null $sender
     * @return Item
     */
    public function addEnchantment(Item $item, $enchants, $levels, $check = true, CommandSender $sender = null)
    {
        if (!is_array($enchants)) {
            $enchants = [$enchants];
        }
        if (!is_array($levels)) {
            $levels = [$levels];
        }
        if (count($enchants) > count($levels)) {
            for ($i = 0; $i <= count($enchants) - count($levels); $i++) {
                $levels[] = 1;
            }
        }
        $combined = array_combine($enchants, $levels);
        foreach ($enchants as $enchant) {
            $level = $combined[$enchant];
            if (!$enchant instanceof CustomEnchants) {
                if (is_numeric($enchant)) {
                    $enchant = CustomEnchants::getEnchantment((int)$enchant);
                } else {
                    $enchant = CustomEnchants::getEnchantmentByName($enchant);
                }
            }
            if ($enchant == null) {
                if ($sender !== null) {
                    $sender->sendMessage(TextFormat::RED . "Invalid enchantment.");
                }
                continue;
            }
            $result = $this->canBeEnchanted($item, $enchant, $level);
            if ($result === true || $check !== true) {
                if ($item->getId() == Item::BOOK) {
                    $item = Item::get(Item::ENCHANTED_BOOK, $level);
                }
                if (!$item->hasCompoundTag()) {
                    $tag = new CompoundTag("", []);
                } else {
                    $tag = $item->getNamedTag();
                }
                if (!isset($tag->ench)) {
                    $tag->ench = new ListTag("ench", []);
                    $tag->ench->setTagType(NBT::TAG_Compound);
                }
                $found = false;
                foreach ($tag->ench as $k => $entry) {
                    if ($entry["id"] === $enchant->getId()) {
                        $tag->ench->{$k} = new CompoundTag("", [
                            "id" => new ShortTag("id", $enchant->getId()),
                            "lvl" => new ShortTag("lvl", $level)
                        ]);
                        $item->setNamedTag($tag);
                        $item->setCustomName(str_replace($this->getRarityColor($enchant->getRarity()) . $enchant->getName() . " " . $this->getRomanNumber($entry["lvl"]), $this->getRarityColor($enchant->getRarity()) . $enchant->getName() . " " . $this->getRomanNumber($level), $item->getName()));
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $tag->ench->{count($tag->ench->getValue()) + 1} = new CompoundTag($enchant->getName(), [
                        "id" => new ShortTag("id", $enchant->getId()),
                        "lvl" => new ShortTag("lvl", $level)
                    ]);
                    $item->setNamedTag($tag);
                    $item->setCustomName($item->getName() . "\n" . $this->getRarityColor($enchant->getRarity()) . $enchant->getName() . " " . $this->getRomanNumber($level));
                }
                if ($sender !== null) {
                    $sender->sendMessage(TextFormat::GREEN . "Enchanting succeeded.");
                }
                continue;
            }
            if ($sender !== null) {
                switch ($result) {
                    case self::NOT_COMPATIBLE:
                        $sender->sendMessage(TextFormat::RED . "The item is not compatible with this enchant.");
                        break;
                    case self::NOT_COMPATIBLE_WITH_OTHER_ENCHANT:
                        $sender->sendMessage(TextFormat::RED . "The enchant is not compatible with another enchant.");
                        break;
                    case self::MAX_LEVEL:
                        $sender->sendMessage(TextFormat::RED . "The max level is " . $this->getEnchantMaxLevel($enchant) . ".");
                        break;

                    case self::MORE_THAN_ONE:
                        $sender->sendMessage(TextFormat::RED . "You can only enchant one item at a time.");
                        break;
                }
            }
            continue;
        }
        return $item;
    }

    /**
     * Removes enchantment from item
     *
     * @param Item $item
     * @param $enchant
     * @param int $level
     * @return bool|Item
     */
    public function removeEnchantment(Item $item, $enchant, $level = -1)
    {
        if (!$item->hasEnchantments()) {
            return false;
        }
        if ($enchant instanceof EnchantmentInstance) {
            $enchant = $enchant->getType();
        }
        $tag = $item->getNamedTag();
        $item = Item::get($item->getId(), $item->getDamage(), $item->getCount());
        foreach ($tag->ench as $k => $enchantment) {
            if (($enchantment["id"] == $enchant->getId() && ($enchantment["lvl"] == $level || $level == -1)) !== true) {
                $item = $this->addEnchantment($item, $enchantment["id"], $enchantment["lvl"], true);
            }
        }
        return $item;
    }

    /**
     * Returns enchantment type
     *
     * @param CustomEnchants $enchant
     * @return string
     */
    public function getEnchantType(CustomEnchants $enchant)
    {
        foreach ($this->enchants as $id => $data) {
            if ($enchant->getId() == $id) {
                return $data[1];
            }
        }
        return "Unknown";
    }

    /**
     * Returns rarity of enchantment
     *
     * @param CustomEnchants $enchant
     * @return string
     */
    public function getEnchantRarity(CustomEnchants $enchant)
    {
        foreach ($this->enchants as $id => $data) {
            if ($enchant->getId() == $id) {
                return $data[3];
            }
        }
        return "Common";
    }

    /**
     * Returns the max level the enchantment can have
     *
     * @param CustomEnchants $enchant
     * @return int
     */
    public function getEnchantMaxLevel(CustomEnchants $enchant)
    {
        foreach ($this->enchants as $id => $data) {
            if ($enchant->getId() == $id) {
                return $data[4];
            }
        }
        return 5;
    }

    /**
     * Returns the description of the enchantment
     *
     * @param CustomEnchants $enchant
     * @return string
     */
    public function getEnchantDescription(CustomEnchants $enchant)
    {
        foreach ($this->enchants as $id => $data) {
            if ($enchant->getId() == $id) {
                return $data[5];
            }
        }
        return "Unknown";
    }

    /**
     * Sorts enchantments by type.
     *
     * @return array
     */
    public function sortEnchants()
    {
        $sorted = [];
        foreach ($this->enchants as $id => $data) {
            $type = $data[1];
            if (!isset($sorted[$type])) {
                $sorted[$type] = [$data[0]];
            } else {
                array_push($sorted[$type], $data[0]);
            }
        }
        return $sorted;
    }

    /**
     * Returns roman numeral of a number
     *
     * @param $integer
     * @return string
     */
    public function getRomanNumber($integer) //Thank you @Muqsit!
    {
        $romanString = "";
        while ($integer > 0) {
            foreach (self::ROMAN_CONVERSION_TABLE as $rom => $arb) {
                if ($integer >= $arb) {
                    $integer -= $arb;
                    $romanString .= $rom;
                    break;
                }
            }
        }
        return $romanString;
    }

    /**
     * Returns the color of a rarity
     *
     * @param $rarity
     * @return string
     */
    public function getRarityColor($rarity)
    {
        switch ($rarity) {
            case CustomEnchants::RARITY_COMMON:
                $color = strtoupper($this->getConfig()->getNested("color.common"));
                return $this->translateColorNameToTextFormat($color) == false ? TextFormat::YELLOW : $this->translateColorNameToTextFormat($color);
            case CustomEnchants::RARITY_UNCOMMON:
                $color = strtoupper($this->getConfig()->getNested("color.uncommon"));
                return $this->translateColorNameToTextFormat($color) == false ? TextFormat::BLUE : $this->translateColorNameToTextFormat($color);
            case CustomEnchants::RARITY_RARE:
                $color = strtoupper($this->getConfig()->getNested("color.rare"));
                return $this->translateColorNameToTextFormat($color) == false ? TextFormat::GOLD : $this->translateColorNameToTextFormat($color);
            case CustomEnchants::RARITY_MYTHIC:
                $color = strtoupper($this->getConfig()->getNested("color.mythic"));
                return $this->translateColorNameToTextFormat($color) == false ? TextFormat::LIGHT_PURPLE : $this->translateColorNameToTextFormat($color);
            default:
                return TextFormat::GRAY;
        }
    }

    /**
     * Translates color name to TextFormat constant
     *
     * @param $color
     * @return bool|mixed
     */
    public function translateColorNameToTextFormat($color)
    {
        foreach (self::COLOR_CONVERSION_TABLE as $name => $textformat) {
            if ($color == $name) {
                return $textformat;
            }
        }
        return false;
    }

    /**
     * Checks if an item can be enchanted with a specific enchantment and level
     *
     * @param Item $item
     * @param $enchant
     * @param $level
     * @return bool
     */
    public function canBeEnchanted(Item $item, $enchant, $level)
    {
        if ($enchant instanceof EnchantmentInstance) {
            $enchant = $enchant->getType();
        } elseif ($enchant instanceof CustomEnchants !== true) {
            $this->getLogger()->error("Argument '$enchant' must be an instance EnchantmentInstance or CustomEnchants.");
            return false;
        }
        $type = $this->getEnchantType($enchant);
        if ($this->getEnchantMaxLevel($enchant) < $level) {
            return self::MAX_LEVEL;
        }
        foreach ($this->incompatibilities as $enchantment => $incompatibilities) {
            if ($item->getEnchantment($enchantment) !== null) {
                if (in_array($enchant->getId(), $incompatibilities)) {
                    return self::NOT_COMPATIBLE_WITH_OTHER_ENCHANT;
                }
            } else {
                foreach ($incompatibilities as $incompatibility) {
                    if ($item->getEnchantment($incompatibility) !== null) {
                        if ($enchantment == $enchant->getId() || in_array($enchant->getId(), $incompatibilities)) {
                            return self::NOT_COMPATIBLE_WITH_OTHER_ENCHANT;
                        }
                    }
                }
            }
        }
        if ($item->getCount() > 1) {
            return self::MORE_THAN_ONE;
        }
        if ($item->getId() == Item::BOOK) {
            return true;
        }
        switch ($type) {
            case "Global":
                return true;
            case "Damageable":
                if ($item->getMaxDurability() !== 0) {
                    return true;
                }
                break;
            case "Weapons":
                if ($item->isSword() !== false || $item->isAxe() || $item->getId() == Item::BOW) {
                    return true;
                }
                break;
            case "Bow":
                if ($item->getId() == Item::BOW) {
                    return true;
                }
                break;
            case "Tools":
                if ($item->isPickaxe() || $item->isAxe() || $item->isShovel() || $item->isShears()) {
                    return true;
                }
                break;
            case "Pickaxe":
                if ($item->isPickaxe()) {
                    return true;
                }
                break;
            case "Axe":
                if ($item->isAxe()) {
                    return true;
                }
                break;
            case "Shovel":
                if ($item->isShovel()) {
                    return true;
                }
                break;
            case "Hoe":
                if ($item->isHoe()) {
                    return true;
                }
                break;
            case "Armor":
                if ($item instanceof Armor) {
                    return true;
                }
                break;
            case "Helmets":
                switch ($item->getId()) {
                    case Item::LEATHER_CAP:
                    case Item::CHAIN_HELMET:
                    case Item::IRON_HELMET:
                    case Item::GOLD_HELMET:
                    case Item::DIAMOND_HELMET:
                        return true;
                }
                break;
            case "Chestplate":
                switch ($item->getId()) {
                    case Item::LEATHER_TUNIC:
                    case Item::CHAIN_CHESTPLATE;
                    case Item::IRON_CHESTPLATE:
                    case Item::GOLD_CHESTPLATE:
                    case Item::DIAMOND_CHESTPLATE:
                    case Item::ELYTRA:
                        return true;
                }
                break;
            case "Leggings":
                switch ($item->getId()) {
                    case Item::LEATHER_PANTS:
                    case Item::CHAIN_LEGGINGS:
                    case Item::IRON_LEGGINGS:
                    case Item::GOLD_LEGGINGS:
                    case Item::DIAMOND_LEGGINGS:
                        return true;
                }
                break;
            case "Boots":
                switch ($item->getId()) {
                    case Item::LEATHER_BOOTS:
                    case Item::CHAIN_BOOTS:
                    case Item::IRON_BOOTS:
                    case Item::GOLD_BOOTS:
                    case Item::DIAMOND_BOOTS:
                        return true;
                }
                break;
            case "Compass":
                if ($item->getId() == Item::COMPASS) {
                    return true;
                }
                break;
        }
        return self::NOT_COMPATIBLE;
    }

    /**
     * Checks for a certain block under a position
     *
     * @param Position $pos
     * @param $ids
     * @param $deep
     * @return bool
     * @internal param $id
     */
    public function checkBlocks(Position $pos, $ids, $deep = 0)
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        if ($deep == 0) {
            $block = $pos->getLevel()->getBlock($pos);
            if (!in_array($block->getId(), $ids)) {
                return false;
            }
        } else {
            for ($i = 0; $deep < 0 ? $i >= $deep : $i <= $deep; $deep < 0 ? $i-- : $i++) {
                $block = $pos->getLevel()->getBlock($pos->subtract(0, $i));
                if (!in_array($block->getId(), $ids)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param Position $position
     * @param int $range
     * @param string $type
     * @param Player|null $player
     * @return null|Entity
     */
    public function findNearestEntity(Position $position, int $range = 50, string $type = Player::class, Player $player = null)
    {
        assert(is_a($type, Entity::class, true));
        $nearestEntity = null;
        $nearestEntityDistance = $range;
        foreach ($position->getLevel()->getEntities() as $entity) {
            $distance = $position->distance($entity);
            if ($distance <= $range && $distance < $nearestEntityDistance && $entity instanceof $type && $player !== $entity && $entity->isAlive() && $entity->isClosed() !== true && $entity->isFlaggedForDespawn() !== true) {
                $nearestEntity = $entity;
                $nearestEntityDistance = $distance;
            }
        }
        return $nearestEntity;
    }
}
