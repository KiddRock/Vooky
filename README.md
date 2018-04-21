
<h1 align="center"> 🔗 Vooky 🔗</h1>


<p align="center"> PocketMine proxy plugin </p>
<br>

<p align="center"> ✔️Latest PocketMine API support </p>


### How to setup?
1) Download plugin phar from releases
2) Restart the server
3) Use /transferserver command or Player->transfer() funcion


### Latest Version:
- 1.0.0
	- not released

### Releases:

- **Stable Builds:**

| Version | Download (PHAR) | Download (ZIP) |
| ------- | --------------- | -------------- |
| 1.0.0 | was not released | was not released |

<br>

- **Other released versions [here](https://github.com/VookyTeam/Vooky/releases)**

### API:

- Transferring player using Vooky

plugin.yml:
```yaml
name: ...
api: ...
depend: [Vooky]
version: ...
```

Main class:
```php
/**  
 * @param \pocketmine\Player $player  
 * @param string $adress  
 * @param int $port  
 */
public function transferPlayerUsingVooky(\pocketmine\Player $player, string $adress, int $port): void {  
	$player->transfer($adress, $port);  
}
```

- Transferring player without Vooky

Main class:
```php
/**  
 * @param \pocketmine\Player $player  
 * @param string $adress  
 * @param int $port  
 */
public function transferPlayerWithoutVooky(\pocketmine\Player $player, string $adress, int $port): void {  
	$pk = new TransferPacket;  
	$pk->address = $adress;  
	$pk->port = $port;  
	$player->dataPacket($pk);  
}
```